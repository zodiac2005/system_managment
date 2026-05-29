// متغير عام للتحكم في النافذة المنبثقة (Modal) الخاصة بـ Bootstrap
let bootstrapModal;

// 1. دالة إضافة منتج جديد
async function addProduct(event) {
    event.preventDefault();
    const type_product = document.getElementById('type_product').value;
    const prix_achat = document.getElementById('prix_achat').value;
    const prix_vente = document.getElementById('prix_vente').value;
    const reference_product = document.getElementById('reference_product').value;

    try {
        const result = await fetch('http://localhost:3001/products', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reference_product, type_product, prix_achat, prix_vente })
        });
        const response = await result.json();
        if (result.ok) {
            alert(response.message);
            document.getElementById('product-form').reset();
        } else {
            alert('Error: ' + response.error);
        }
    } catch (error) {
        console.error("خطأ في الاتصال بالسيرفر", error);
        alert('خطأ في الاتصال بالسيرفر');
    }
}

// 2. دالة فتح الـ Modal وجلب مراجع المنتجات من السيرفر
async function editProduct() {
    try {
        const response = await fetch('http://localhost:3001/products');
        if (!response.ok) throw new Error('فشل جلب المنتجات');
        
        const products = await response.json();
        const selectElement = document.getElementById('productSelect');
        
        // إعادة تهيئة القائمة المنسدلة
        selectElement.innerHTML = '<option value="">-- Choose Reference --</option>';
        
        // تعبئة الـ Select بالمنتجات
        products.forEach(prod => {
            const option = document.createElement('option');
            option.value = prod.id_product; // نستخدم الـ ID كقيمة للمنتج
            option.textContent = `${prod.reference_product} (${prod.type_product})`;
            selectElement.appendChild(option);
        });

        // إظهار النافذة المنبثقة
        bootstrapModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        bootstrapModal.show();

    } catch (error) {
        console.error(error);
        alert('حدث خطأ أثناء جلب قائمة المنتجات');
    }
}

// 3. عند تغيير الاختيار في الـ Select، يتم جلب تفاصيل المنتج المختار تلقائياً
const productSelect = document.getElementById('productSelect');
if (productSelect) {
    productSelect.addEventListener('change', async function() {
        const id_product = this.value;
        if (!id_product) return;

        try {
            const response = await fetch(`http://localhost:3001/products/${id_product}`);
            if (response.ok) {
                const product = await response.json();
                document.getElementById('edit_type_product').value = product.type_product;
                document.getElementById('edit_prix_achat').value = product.prix_achat;
                document.getElementById('edit_prix_vente').value = product.prix_vente;
            }
        } catch (error) {
            console.error(error);
        }
    });
}

// 4. دالة حفظ التعديلات الجديدة (Save Changes)
async function saveProductUpdate() {
    const id_product = document.getElementById('productSelect').value;
    const type_product = document.getElementById('edit_type_product').value;
    const prix_achat = document.getElementById('edit_prix_achat').value;
    const prix_vente = document.getElementById('edit_prix_vente').value;

    if (!id_product) {
        alert('Please select a product first');
        return;
    }

    try {
        const result = await fetch(`http://localhost:3001/products/${id_product}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type_product, prix_achat, prix_vente })
        });
        
        const response = await result.json();
        if (result.ok) {
            alert('Product updated successfully!');
            if (bootstrapModal) bootstrapModal.hide();
        } else {
            alert('Error: ' + response.error);
        }
    } catch (error) {
        console.error(error);
        alert('خطأ في الاتصال بالسيرفر أثناء التعديل');
    }
}

// 5. دالة حذف المنتج (هذه هي الدالة التي كانت تنقصك وتسبّب الخطأ)
async function deleteProduct() {
    const id_product = document.getElementById('productSelect').value;

    if (!id_product) {
        alert('Please select a product to delete first');
        return;
    }

    if (confirm('Are you sure you want to delete this product?')) {
        try {
            const result = await fetch(`http://localhost:3001/products/${id_product}`, {
                method: 'DELETE'
            });

            const response = await result.json();

            if (result.ok) {
                alert(response.message);
                
                // إغلاق الـ Modal
                if (bootstrapModal) bootstrapModal.hide();
                
                // تفريغ الحقول بعد الحذف بنجاح
                document.getElementById('edit_type_product').value = '';
                document.getElementById('edit_prix_achat').value = '';
                document.getElementById('edit_prix_vente').value = '';
            } else {
                alert('Error: ' + response.error);
            }
        } catch (error) {
            console.error(error);
            alert('Error connecting to server while deleting');
        }
    }
}

// ربط الأحداث بالأزرار الرئيسية
const productForm = document.getElementById('product-form');
if (productForm) {
    productForm.addEventListener('submit', addProduct);
}

const editButton = document.getElementById('Edit_product');
if (editButton) {
    editButton.addEventListener('click', editProduct);
}