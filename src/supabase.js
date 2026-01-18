
import { createClient } from '@supabase/supabase-js'

const supabaseUrl = 'https://misruonprtzpsumureur.supabase.co'
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1pc3J1b25wcnR6cHN1bXVyZXVyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3Njg3NDIzMTMsImV4cCI6MjA4NDMxODMxM30.Krk0b6Y3KpbBeZZx2ieRDvcGwA_yD7YW1y9eBn5oH4c'

export const supabase = createClient(supabaseUrl, supabaseKey)
