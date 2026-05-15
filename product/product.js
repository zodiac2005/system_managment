async function addProduct(event) {
    event.preventDefault();
    const type_product = document.getElementById('type_product').value;
const prix_achat = document.getElementById('prix_achat').value;
const prix_vente = document.getElementById('prix_vente').value;

try{
    const result=await fetch('http://localhost:3001/products', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        type_product: type_product,
        prix_achat: prix_achat,
        prix_vente: prix_vente
    })
});
const response = await result.json();
if (result.ok) {
    alert(response.message);
    document.getElementById('product-form').reset();
}

 else {
    alert('Error: ' + response.error);
}

}
catch{
    console.error("خطأ في الاتصال بالسيرفر");
    alert('خطأ في الاتصال بالسيرفر');
}

}
const productForm = document.getElementById('product-form');
if (productForm) {
    productForm.addEventListener('submit', addProduct);

}
else{ 
       console.log('productForm not found');
    }