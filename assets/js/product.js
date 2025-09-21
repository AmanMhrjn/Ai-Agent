let barChart, pagePieChart, platformPieChart;

function initCharts() {
    const barCtx = document.getElementById('barChart').getContext('2d');
    barChart = new Chart(barCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Messages Consumed', data: [], backgroundColor: 'rgba(54, 162, 235, 0.7)' }] },
        options: { responsive: true, scales: { x: { title: { display: true, text: 'Date' } }, y: { beginAtZero: true, title: { display: true, text: 'Messages' } } } }
    });

    const pageCtx = document.getElementById('pagePieChart').getContext('2d');
    pagePieChart = new Chart(pageCtx, {
        type: 'pie',
        data: { labels: [], datasets: [{ label: 'Pages', data: [], backgroundColor: [] }] },
        options: { responsive: true }
    });

    const platformCtx = document.getElementById('platformPieChart').getContext('2d');
    platformPieChart = new Chart(platformCtx, {
        type: 'pie',
        data: { labels: [], datasets: [{ label: 'Platform Plan', data: [], backgroundColor: [] }] },
        options: { responsive: true }
    });
}

function getRandomColor() {
    const r = Math.floor(Math.random() * 200) + 30;
    const g = Math.floor(Math.random() * 200) + 30;
    const b = Math.floor(Math.random() * 200) + 30;
    return `rgba(${r},${g},${b},0.7)`;
}

function updateCharts(data) {
    barChart.data.labels = data.barGraph.labels;
    barChart.data.datasets[0].data = data.barGraph.data;
    barChart.update();

    pagePieChart.data.labels = data.pagePie.labels;
    pagePieChart.data.datasets[0].data = data.pagePie.data;
    pagePieChart.data.datasets[0].backgroundColor = data.pagePie.labels.map(() => getRandomColor());
    pagePieChart.update();

    platformPieChart.data.labels = data.platformPie.labels;
    platformPieChart.data.datasets[0].data = data.platformPie.data;
    platformPieChart.data.datasets[0].backgroundColor = data.platformPie.labels.map(() => getRandomColor());
    platformPieChart.update();
}

// =========== PRODUCT SECTION ============
let currentPage = 1;
let totalPages = 1;
let products = [];

async function loadProducts(page = 1) {
    try {
        const res = await fetch(`getProducts.php?page=${page}`);
        const data = await res.json();

        products = data.products;
        totalPages = Math.ceil(data.total / data.limit);
        currentPage = page;

        renderProducts();
        renderPagination();
    } catch (err) {
        console.error("Error loading products:", err);
    }
}

function renderProducts() {
    const tbody = document.querySelector('#productTable tbody');
    tbody.innerHTML = '';

    products.forEach(product => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <img src="uploads/${product.photo_1}" alt="Photo 1" style="width: 50px; height: auto;" />
                <img src="uploads/${product.photo_2}" alt="Photo 2" style="width: 50px; height: auto;" />
            </td>
            <td>${product.product_name}</td>
            <td>${product.offer_price}</td>
            <td>${product.color}</td>
            <td>${product.warranty}</td>
            <td>${product.availability}</td>
            <td>${product.description}</td>
            <td>
                <button class="action-btn" onclick="window.location.href='updateProduct.php?id=${product.id}'">Update</button> |
                <button class="action-btn" onclick="deleteProduct(${product.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}


function renderPagination() {
    let existing = document.getElementById('pagination');
    if (existing) existing.remove();

    const container = document.querySelector('.product-table-section');
    const div = document.createElement('div');
    div.id = 'pagination';
    div.style.textAlign = 'center';
    div.style.marginTop = '15px';

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.style.margin = '0 5px';
        btn.disabled = (i === currentPage);
        btn.onclick = () => loadProducts(i);
        div.appendChild(btn);
    }

    container.appendChild(div);
}

function deleteProduct(id) {
    if (confirm("Are you sure you want to delete this product?")) {
        window.location.href = `deleteProduct.php?id=${id}`;
    }
}

function toggleStatus(id) {
    window.location.href = `toggleStatus.php?id=${id}`;
}

// =========== ORDER SECTION =============
let orders = [
    { id: 1, product: 'Product A', quantity: 10, status: 'Pending' },
    { id: 2, product: 'Product B', quantity: 5, status: 'Shipped' },
    { id: 3, product: 'Product C', quantity: 2, status: 'Delivered' },
];

function renderOrders() {
    const tbody = document.querySelector('#orderTable tbody');
    tbody.innerHTML = '';
    orders.forEach((order, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${order.id}</td>
            <td>${order.product}</td>
            <td>${order.quantity}</td>
            <td>${order.status}</td>
            <td><button class="order-button" onclick="changeOrderStatus(${index})">Change Status</button></td>
        `;
        tbody.appendChild(tr);
    });
}

function changeOrderStatus(index) {
    const newStatus = prompt('Enter new status for Order ID ' + orders[index].id, orders[index].status);
    if (newStatus !== null && newStatus.trim() !== '') {
        orders[index].status = newStatus.trim();
        renderOrders();
    }
}

// =========== CHART DATA FETCH ============
async function fetchData(startDate, endDate) {
    try {
        const response = await fetch(`api2.php?start_date=${startDate}&end_date=${endDate}`);
        const data = await response.json();
        if (data.error) {
            alert(data.error);
            return;
        }
        updateCharts(data);
    } catch (error) {
        alert('Error fetching data: ' + error.message);
    }
}

document.getElementById('update-btn').addEventListener('click', () => {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return;
    }
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be after end date.');
        return;
    }
    fetchData(startDate, endDate);
});

window.onload = () => {
    initCharts();

    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - 7);
    document.getElementById('start-date').value = start.toISOString().split('T')[0];
    document.getElementById('end-date').value = end.toISOString().split('T')[0];

    fetchData(start.toISOString().split('T')[0], end.toISOString().split('T')[0]);
    loadProducts(1);
    renderOrders();
};

function toggleStatus(id) {
  if (confirm("Are you sure you want to change the product status?")) {
    window.location.href = `enableDisable.php?id=${id}`;
  }
}

