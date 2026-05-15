const express = require('express');
const mariaDB = require('mariadb');
const cors = require('cors');
const app = express();
app.use(cors());
app.use(express.json());

const pool = mariaDB.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'mydb',
    connectionLimit: 5
});
app.get('/products', async (req, res) => {
    try {
        const products = await pool.query('SELECT id_product, type_product FROM product');
        res.json(products);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    }
});

app.post('/products', async (req, res) => {
    const { type_product, prix_achat, prix_vente } = req.body;
    try {
        const conn = await pool.getConnection();
        if (!type_product || !prix_achat || !prix_vente) {
            res.status(400).json({ error: 'Missing required fields' });
            return;
        }
        const result = await conn.query('INSERT INTO product (type_product, prix_achat, prix_vente) VALUES (?, ?, ?)', [type_product, prix_achat, prix_vente]);
        console.log(result);
        res.status(201).json({ message: 'Product added successfully' });

    
    } 
    
    catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    }
});


app.listen(3001, () => {
    console.log('Server is running on port 3001');
});