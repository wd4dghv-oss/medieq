import express from 'express';
import pkg from 'pg';
const { Pool } = pkg;
import cors from 'cors';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcrypt';
import crypto from 'crypto';

const app = express();
app.use(cors());
app.use(express.json());

// IMPORTANT: Replace this with your actual Neon connection string
const DATABASE_URL = process.env.DATABASE_URL || 'postgresql://neondb_owner:npg_Aedjtg4zm0Kr@ep-noisy-credit-adkdbwgm-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require';

const pool = new Pool({
  connectionString: DATABASE_URL,
});

const SECRET = 'medieq_secret_key_12345';

async function initDB() {
    try {
        await pool.query(`CREATE TABLE IF NOT EXISTS users (id TEXT PRIMARY KEY, email TEXT UNIQUE, password_hash TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)`);
        await pool.query(`CREATE TABLE IF NOT EXISTS profiles (id TEXT PRIMARY KEY, email TEXT, ward TEXT, role TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)`);
        await pool.query(`CREATE TABLE IF NOT EXISTS devices (id SERIAL PRIMARY KEY, device_id TEXT UNIQUE, device_name TEXT, device_group TEXT, ward TEXT, status TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)`);
        await pool.query(`CREATE TABLE IF NOT EXISTS charging_charts (id SERIAL PRIMARY KEY, device_id TEXT, device_name TEXT, device_group TEXT, ward TEXT, charging_date TEXT, charging_start TEXT, charging_end TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)`);
        await pool.query(`CREATE TABLE IF NOT EXISTS bme_charts (id SERIAL PRIMARY KEY, device_id TEXT, device_name TEXT, device_group TEXT, ward TEXT, reason TEXT, status TEXT, send_date TEXT, receive_date TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)`);
        console.log("Database initialized successfully.");
    } catch (err) {
        console.error("Error initializing database:", err);
    }
}
initDB();

const authMiddleware = (req, res, next) => {
    const header = req.headers.authorization;
    if (!header) return res.status(401).json({ error: 'No token provided' });
    const token = header.split(' ')[1];
    jwt.verify(token, SECRET, (err, decoded) => {
        if (err) return res.status(401).json({ error: 'Invalid token' });
        req.user = decoded;
        next();
    });
};

app.post('/api/auth/signup', async (req, res) => {
    const { email, password } = req.body;
    if (!email || !password) return res.status(400).json({ error: 'Missing email or password' });

    try {
        const { rows } = await pool.query(`SELECT id FROM users WHERE email = $1`, [email.toLowerCase()]);
        if (rows.length > 0) return res.status(400).json({ error: 'User already exists' });
        
        const id = crypto.randomUUID();
        const hash = await bcrypt.hash(password, 10);
        
        await pool.query(`INSERT INTO users (id, email, password_hash) VALUES ($1, $2, $3)`, [id, email.toLowerCase(), hash]);
        res.json({ user: { id, email: email.toLowerCase() } });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.post('/api/auth/signin', async (req, res) => {
    const { email, password } = req.body;
    try {
        const { rows } = await pool.query(`SELECT * FROM users WHERE email = $1`, [email.toLowerCase()]);
        const user = rows[0];
        if (!user) return res.status(400).json({ error: 'Invalid credentials' });
        
        const valid = await bcrypt.compare(password, user.password_hash);
        if (!valid) return res.status(400).json({ error: 'Invalid credentials' });
        
        const token = jwt.sign({ id: user.id, email: user.email }, SECRET, { expiresIn: '7d' });
        res.json({ access_token: token, user: { id: user.id, email: user.email } });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.get('/api/auth/session', authMiddleware, (req, res) => {
    res.json({ user: { id: req.user.id, email: req.user.email } });
});

app.post('/api/auth/update', authMiddleware, async (req, res) => {
    const { password } = req.body;
    if (password) {
        try {
            const hash = await bcrypt.hash(password, 10);
            await pool.query(`UPDATE users SET password_hash = $1 WHERE id = $2`, [hash, req.user.id]);
            res.json({ user: req.user });
        } catch (err) {
            res.status(500).json({ error: err.message });
        }
    } else {
        res.json({ user: req.user });
    }
});

app.post('/api/rpc/get_unique_wards', async (req, res) => {
    try {
        const { rows } = await pool.query(`SELECT DISTINCT ward AS ward_name FROM profiles WHERE ward IS NOT NULL`);
        res.json({ data: rows });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.post('/api/query', async (req, res) => {
    const ast = req.body;
    const table = ast.table;
    const action = ast.action;
    
    // Whitelist tables
    if (!['profiles', 'devices', 'charging_charts', 'bme_charts'].includes(table)) {
        return res.status(400).json({ error: 'Invalid table' });
    }

    try {
        if (action === 'select') {
            let selectCols = ast.select === '*' ? '*' : ast.select;
            let query = `SELECT ${selectCols} FROM ${table}`;
            let params = [];
            let whereConds = [];
            let paramIndex = 1;
            
            ast.filters.forEach(f => {
                if (f.type === 'eq') {
                    whereConds.push(`${f.col} = $${paramIndex++}`);
                    params.push(f.val);
                } else if (f.type === 'is') {
                    if (f.val === null) whereConds.push(`${f.col} IS NULL`);
                    else { whereConds.push(`${f.col} IS $${paramIndex++}`); params.push(f.val); }
                } else if (f.type === 'in') {
                    const placeholders = f.valArr.map(() => `$${paramIndex++}`).join(',');
                    whereConds.push(`${f.col} IN (${placeholders})`);
                    params.push(...f.valArr);
                }
            });

            if (whereConds.length > 0) {
                query += ` WHERE ${whereConds.join(' AND ')}`;
            }

            if (ast.order) {
                query += ` ORDER BY ${ast.order.col} ${ast.order.ascending ? 'ASC' : 'DESC'}`;
            }
            
            if (ast.single || ast.maybeSingle) {
                query += ` LIMIT 1`;
                const { rows } = await pool.query(query, params);
                if (ast.single && rows.length === 0) return res.status(404).json({ error: 'Not found' });
                res.json({ data: rows[0] || null });
            } else {
                const { rows } = await pool.query(query, params);
                res.json({ data: rows });
            }
        } 
        else if (action === 'insert') {
            const payloads = Array.isArray(ast.payload) ? ast.payload : [ast.payload];
            if (payloads.length === 0) return res.json({ data: [] });
            
            const cols = Object.keys(payloads[0]);
            
            const client = await pool.connect();
            try {
                await client.query('BEGIN');
                let lastId = null;
                for (const payload of payloads) {
                    let paramIndex = 1;
                    const placeholders = cols.map(() => `$${paramIndex++}`).join(',');
                    const values = cols.map(c => payload[c]);
                    const { rows } = await client.query(`INSERT INTO ${table} (${cols.join(',')}) VALUES (${placeholders}) RETURNING id`, values);
                    if(rows[0]) lastId = rows[0].id;
                }
                await client.query('COMMIT');
                res.json({ data: { id: lastId } });
            } catch (e) {
                await client.query('ROLLBACK');
                throw e;
            } finally {
                client.release();
            }
        }
        else if (action === 'update') {
            const payload = ast.payload;
            const cols = Object.keys(payload);
            let paramIndex = 1;
            const setClause = cols.map(c => `${c} = $${paramIndex++}`).join(', ');
            let params = cols.map(c => payload[c]);
            
            let whereConds = [];
            ast.filters.forEach(f => {
                if (f.type === 'eq') {
                    whereConds.push(`${f.col} = $${paramIndex++}`);
                    params.push(f.val);
                }
            });

            const { rowCount } = await pool.query(`UPDATE ${table} SET ${setClause} WHERE ${whereConds.length ? whereConds.join(' AND ') : '1=1'}`, params);
            res.json({ data: { changes: rowCount } });
        }
        else if (action === 'delete') {
            let params = [];
            let whereConds = [];
            let paramIndex = 1;

            ast.filters.forEach(f => {
                if (f.type === 'eq') {
                    whereConds.push(`${f.col} = $${paramIndex++}`);
                    params.push(f.val);
                }
            });

            const { rowCount } = await pool.query(`DELETE FROM ${table} WHERE ${whereConds.length ? whereConds.join(' AND ') : '1=1'}`, params);
            res.json({ data: { changes: rowCount } });
        }
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// For Vercel Serverless Functions
export default app;
