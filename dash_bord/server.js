
const express = require('express');
const mariadb = require('mariadb');
const cors = require('cors');
const bcrypt = require("bcrypt");

const app = express();
app.use(cors());
app.use(express.json());
 app.use(express.urlencoded({ extended: true }));

const pool = mariadb.createPool({
    host: '127.0.0.1',
    user: 'root',
    password: '',
    database: 'mydb',
    connectionLimit: 5
});


// app.get("/", (req, res) => {
//     res.send("server works");
// });




app.post('/register', async (req, res) => {

    try {

        const { name, email, password } = req.body;

        const checkSql = `
            SELECT * FROM compte_gestieuneur 
            WHERE name = ? OR Email = ?
        `;

        const existingUsers = await pool.query(checkSql, [name, email]);

        if (existingUsers.length > 0) {
            return res.status(409).json({
                success: false,
                message: "name or email already exists"
            });
        }

        const hashedPassword = await bcrypt.hash(password, 10);

        const sql = `
            INSERT INTO compte_gestieuneur (name, Email, password)
            VALUES (?, ?, ?)
        `;

        await pool.query(sql, [name, email, hashedPassword]);

        return res.status(201).json({
            success: true,
            message: "user created"
        });

    } catch (error) {

        return res.status(500).json({
            success: false,
            message: "server error",
            error: error.message
        });

    }

});        
     


//         // hashing password
//         const hashedPassword = await bcrypt.hash(password, 10);

//         const sql = `INSERT INTO compte_gestieuneur (name,Email, password) VALUES (?,?,?)`;
           

  
//      pool.query(
//     sql,
//     [name, email, hashedPassword],
//     (err, result) => {

//         if(err){

//             return res.status(500).json({
//                 success: false,
//                 message: "database error",
//                 error: err.message
//             });

//         }

//         return res.status(201).json({
//             success: true,
//             message: "user created"
//         });

//     }
// );

    





app.post("/login", async (req, res) => {
    const { name, password } = req.body 

    // if (!name || !password) {
    //     return res.status(400).json({
    //         message: "name and password are required"
    //     });
    // }

    try {
        const sql = `
            SELECT * FROM compte_gestieuneur
            WHERE name = ?
        `;

        const result = await pool.query(sql, [name]);

        if (!result || result.length === 0) {
            return res.status(404).json({
                message: "user not found"
            });
        }

        const user = result[0];
        const storedPassword = String(user.password );
        // had compare  kat9arn bin version simple d password li jat man user o version hashed li kayna f database
        // o bach tkon logic compration mathod compare() k thachi password li ja man user
        const isMatch = await bcrypt.compare(password, storedPassword);
        console.log( isMatch);
        console.log("Stored password:", storedPassword);
        console.log("Provided password:", password);

        if (isMatch) {
            return res.status(200).json({
                success: true,
                message: "login successful"
     
            });
        }

        res.json({
            success: false,
            message: "login failed",
            user: { name: user.name, email: user.Email }
        });
    } catch (err) {
        return res.status(500).json({
            message: "database error",
            error: err.message
        });
    }
});

app.get("/sales", async (req, res) => {
    try{
        const sql = `SELECT 
    COALESCE(SUM(quantity * prix), 0.00) AS total_sales_today,
    COUNT(id_facture_details) AS product_sold
FROM 
    facture_details
WHERE 
    DATE(date_facture) = CURRENT_DATE;`;
        const result = await pool.query(sql);
        // Convert BigInt fields (returned by the driver) to numbers/strings
        const sanitized = result.map(row => {
            const out = {};
            for (const key of Object.keys(row)) {
                const val = row[key];
                out[key] = (typeof val === 'bigint') ? Number(val) : val;
               
            }
            return out;
        });
        console.log('Sanitized result:', sanitized);

        return res.status(200).json({
            success: true,
            data: sanitized
        });
    }
    catch(err){
        return res.status(500).json({
            message: "database error",
            error: err.message
        });
    }
});






app.listen(3000, () => console.log(' السيرفر يعمل على http://localhost:3000'));