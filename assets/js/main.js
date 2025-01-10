// Main JavaScript file for VillageCart

// Show/hide password toggle
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = document.querySelector(this.getAttribute('data-target'));
        if (input.type === 'password') {
            input.type = 'text';
            this.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = 'password';
            this.innerHTML = '<i class="fas fa-eye"></i>';
        }
    });
});

// Add to cart functionality
function addToCart(productId, quantity = 1) {
    fetch(`${SITE_URL}/ajax/add-to-cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
            
            // Show success message
            showAlert('Product added to cart successfully!', 'success');
        } else {
            showAlert(data.message || 'Error adding product to cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error adding product to cart', 'danger');
    });
}

// Show alert message
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
