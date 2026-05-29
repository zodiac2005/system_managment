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
    database: 'mydb_store',
    connectionLimit: 30
});

// 1. جلب كل المنتجات (تم تعديل الاستعلام ليشمل الـ reference)
app.get('/products', async (req, res) => {
    try {
        const products = await pool.query('SELECT id_product, reference_product, type_product FROM product');
        res.json(products);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    }
});

// 2. جلب تفاصيل منتج واحد محدد عبر الـ ID
app.get('/products/:id', async (req, res) => {
    try {
        const rows = await pool.query('SELECT * FROM product WHERE id_product = ?', [req.params.id]);
        if (rows.length > 0) {
            res.json(rows[0]);
        } else {
            res.status(404).json({ error: 'Product not found' });
        }
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    }
});

// 3. إضافة منتج جديد
app.post('/products', async (req, res) => {
    const { reference_product, type_product, prix_achat, prix_vente } = req.body;
    let conn;
    try {
        conn = await pool.getConnection();
        if (!reference_product || !type_product || !prix_achat || !prix_vente) {
            res.status(400).json({ error: 'Missing required fields' });
            return;
        }
        await conn.query('INSERT INTO product (reference_product, type_product, prix_achat, prix_vente) VALUES (?, ?, ?, ?)', [reference_product, type_product, prix_achat, prix_vente]);
        res.status(201).json({ message: 'Product added successfully' });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    } finally {
        if (conn) conn.release();
    }
});

// 4. تحديث بيانات منتج معين (PUT)
app.put('/products/:id', async (req, res) => {
    const { type_product, prix_achat, prix_vente } = req.body;
    let conn;
    try {
        conn = await pool.getConnection();
        if (!type_product || !prix_achat || !prix_vente) {
            res.status(400).json({ error: 'Missing required fields' });
            return;
        }
        await conn.query(
            'UPDATE product SET type_product = ?, prix_achat = ?, prix_vente = ? WHERE id_product = ?',
            [type_product, prix_achat, prix_vente, req.params.id]
        );
        res.json({ message: 'Product updated successfully' });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    } finally {
        if (conn) conn.release();
    }
});

// 5. حذف منتج معين عبر الـ ID (DELETE)
app.delete('/products/:id', async (req, res) => {
    let conn;
    try {
        conn = await pool.getConnection();
        const id_product = req.params.id;

        const result = await conn.query('DELETE FROM product WHERE id_product = ?', [id_product]);

        // التحقق مما إذا كان هناك منتج تم حذفه بالفعل في قاعدة البيانات
        if (result.affectedRows > 0) {
            res.json({ message: 'Product deleted successfully' });
        } else {
            res.status(404).json({ error: 'Product not found or already deleted' });
        }
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Internal Server Error' });
    } finally {
        if (conn) conn.release();
    }
});

app.listen(3001, () => {
    console.log('Server is running on port 3001');
});