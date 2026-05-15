async function loadProducts() {
    try {
        const response = await fetch('http://localhost:3001/products');
        const products = await response.json();
        const productSelect = document.getElementById('product');

        products.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id_product;
            option.textContent = item.type_product;
            productSelect.appendChild(option);
        });
    } 
    catch (error) {
        console.error('Error loading products:', error);
    }
}

async function stoke(e) {
    e.preventDefault();
    const type_product = document.getElementById('product');
    const quantity = document.getElementById('quantity');

    if (!type_product.value) {
        alert('Please select a product.');
        return;
    }

    try {
        await fetch('http://localhost:3002/stoke', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type_product: type_product.value,
                quantity: quantity.value
            })
        });
    } 
    catch (error) {
        console.error('Error adding to stoke:', error);
    }
}


    loadProducts();
    const form = document.getElementById('form');
    if (form) {
        form.addEventListener('submit', stoke);
    }
