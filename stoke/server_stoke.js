const express = require('express');
const mariadb = require('mariadb');
const cors = require('cors');
const app = express();
app.use(cors());
app.use(express.json());
const pool = mariadb.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'mydb_store',
    connectionLimit: 30
});

app.post('/stoke', async (req, res) => {
    const { quantity,type_product} = req.body;
    try {
        const conn = await pool.getConnection();
        await conn.query('INSERT INTO stock ( product_id,quantity) VALUES (?, ?)', [ type_product, quantity]);
        res.status(201).json({ message: 'Stock updated successfully' });
        
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    }

});

app.listen(3002, () => {
    console.log('Server is running on port 3002');
});
