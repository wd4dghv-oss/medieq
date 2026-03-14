const API_URL = 'http://localhost:3000';

class QueryBuilder {
  constructor(table) {
    this.ast = { table, action: 'select', filters: [], select: '*', order: null, single: false, maybeSingle: false, payload: null };
  }
  
  select(cols = '*') { this.ast.action = 'select'; this.ast.select = cols; return this; }
  insert(payload) { this.ast.action = 'insert'; this.ast.payload = payload; return this; }
  update(payload) { this.ast.action = 'update'; this.ast.payload = payload; return this; }
  delete() { this.ast.action = 'delete'; return this; }
  
  eq(col, val) { this.ast.filters.push({ type: 'eq', col, val }); return this; }
  in(col, valArr) { this.ast.filters.push({ type: 'in', col, valArr }); return this; }
  is(col, val) { this.ast.filters.push({ type: 'is', col, val }); return this; }
  
  order(col, opts = { ascending: true }) { this.ast.order = { col, ascending: opts.ascending }; return this; }
  
  single() { this.ast.single = true; return this; }
  maybeSingle() { this.ast.maybeSingle = true; return this; }
  
  async then(resolve, reject) {
    try {
      const token = localStorage.getItem('access_token');
      const resp = await fetch(`${API_URL}/api/query`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        body: JSON.stringify(this.ast)
      });
      const data = await resp.json();
      if (!resp.ok) return resolve({ data: null, error: new Error(data.error) });
      return resolve({ data: data.data, error: null });
    } catch(e) {
      resolve({ data: null, error: e });
    }
  }
}

let authListeners = [];

export const supabase = {
  from: (table) => new QueryBuilder(table),
  rpc: async (methodName, params) => {
    try {
      const resp = await fetch(`${API_URL}/api/rpc/${methodName}`, {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(params||{})
      })
      const data = await resp.json()
      if (!resp.ok) return { data: null, error: new Error(data.error) }
      return { data: data.data, error: null }
    } catch(e) { return { data: null, error: e } }
  },
  auth: {
    getSession: async () => {
      const token = localStorage.getItem('access_token');
      if (!token) return { data: { session: null }, error: null }
      try {
        const resp = await fetch(`${API_URL}/api/auth/session`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        const data = await resp.json()
        if (!resp.ok) {
           localStorage.removeItem('access_token');
           return { data: { session: null }, error: null }
        }
        return { data: { session: { user: data.user } }, error: null }
      } catch(e) {
        return { data: { session: null }, error: e }
      }
    },
    getUser: async () => supabase.auth.getSession().then(({data}) => ({data: { user: data.session?.user }, error: null})),
    signInWithPassword: async ({email, password}) => {
      try {
        const resp = await fetch(`${API_URL}/api/auth/signin`, {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({email, password})
        })
        const data = await resp.json()
        if (!resp.ok) return { data: null, error: new Error(data.error) }
        localStorage.setItem('access_token', data.access_token)
        authListeners.forEach(cb => cb('SIGNED_IN', { user: data.user }))
        return { data: { user: data.user }, error: null }
      } catch (err) {
        return { data: null, error: err }
      }
    },
    signUp: async ({email, password}) => {
      try {
        const resp = await fetch(`${API_URL}/api/auth/signup`, {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({email, password})
        })
        const data = await resp.json()
        if (!resp.ok) return { data: null, error: new Error(data.error) }
        return { data: { user: data.user }, error: null }
      } catch (err) {
        return { data: null, error: err }
      }
    },
    signOut: async () => {
      localStorage.removeItem('access_token')
      authListeners.forEach(cb => cb('SIGNED_OUT', null))
      return { error: null }
    },
    updateUser: async (updates) => {
      try {
        const token = localStorage.getItem('access_token');
        const resp = await fetch(`${API_URL}/api/auth/update`, {
           method: 'POST', headers: {'Content-Type': 'application/json', 'Authorization': `Bearer ${token}`}, body: JSON.stringify(updates)
        })
        const data = await resp.json()
        if (!resp.ok) return { data: null, error: new Error(data.error) }
        return { data: { user: data.user }, error: null }
      } catch (err) {
        return { data: null, error: err }
      }
    },
    onAuthStateChange: (cb) => {
      authListeners.push(cb)
      return { data: { subscription: { unsubscribe: () => { authListeners = authListeners.filter(f=>f!==cb) } } } }
    }
  }
}
