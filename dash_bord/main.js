const totalSales = document.getElementById('total-sales');
const totalProducts = document.getElementById('product-count');
async function fetchTotalSales() {
    try {
        const response = await fetch('http://localhost:3000/sales', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        if (response.ok && result.success) {
            totalSales.textContent = result.data[0].total_sales_today + ' MAD';
            if (totalProducts) {
                totalProducts.textContent = result.data[0].product_sold + ' products';
            }
        } else {
            totalSales.textContent = 'Error fetching sales';
        }
    } catch (err) {
        console.error('Error fetching total sales:', err);
        totalSales.textContent = 'Error fetching sales';

    }
}
fetchTotalSales();

