// Dashboard JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize equipment chart for admin dashboard
    if (document.getElementById('equipmentChart')) {
        initializeEquipmentChart();
    }
    
    // Initialize calendar for faculty dashboard
    if (document.getElementById('calendarGrid')) {
        initializeCalendar();
    }
    
    // Add click handlers for action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const actionText = this.querySelector('.action-text').textContent;
            if (actionText.includes('EQUIPMENT LIST')) {
                // Navigate to equipment list page (to be implemented)
                console.log('Navigate to equipment list');
            } else if (actionText.includes('Request Transfer')) {
                // Navigate to transfer request page (to be implemented)
                console.log('Navigate to transfer request');
            }
        });
    });
});

function initializeEquipmentChart() {
    const ctx = document.getElementById('equipmentChart').getContext('2d');
    
    // Sample data based on the chart in the image
    const chartData = {
        labels: ['All in One PC', 'Mouse', 'Laptop', 'Desktop', 'Keyboard'],
        datasets: [{
            label: 'Equipment Count',
            data: [25, 15, 35, 30, 20], // Sample data
            backgroundColor: [
                '#ff9999', // Light red
                '#ffcc99', // Light orange
                '#99ff99', // Light green
                '#99ccff', // Light blue
                '#ff99cc'  // Light pink
            ],
            borderColor: [
                '#ff6666',
                '#ff9966',
                '#66ff66',
                '#6699ff',
                '#ff6699'
            ],
            borderWidth: 2
        }]
    };
    
    const config = {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'white',
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.2)'
                    }
                },
                x: {
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.2)'
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

function initializeCalendar() {
    const calendarGrid = document.getElementById('calendarGrid');
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();
    
    // Days of the week headers
    const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    // Add day headers
    daysOfWeek.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
    });
    
    // Get first day of month and number of days
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
    
    // Add previous month's trailing days
    for (let i = firstDay - 1; i >= 0; i--) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day other-month';
        dayElement.textContent = daysInPrevMonth - i;
        calendarGrid.appendChild(dayElement);
    }
    
    // Add current month's days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        // Highlight today
        if (day === currentDate.getDate() && 
            currentMonth === new Date().getMonth() && 
            currentYear === new Date().getFullYear()) {
            dayElement.classList.add('today');
        }
        
        calendarGrid.appendChild(dayElement);
    }
    
    // Add next month's leading days
    const totalCells = calendarGrid.children.length - 7; // Subtract headers
    const remainingCells = 42 - totalCells; // 6 rows × 7 days - headers
    
    for (let day = 1; day <= remainingCells; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day other-month';
        dayElement.textContent = day;
        calendarGrid.appendChild(dayElement);
    }
}

// Utility function to format dates
function formatDate(date) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return date.toLocaleDateString('en-US', options);
}

// Handle responsive navigation toggle
function toggleMobileNav() {
    const navbarCollapse = document.querySelector('.navbar-collapse');
    if (navbarCollapse) {
        navbarCollapse.classList.toggle('show');
    }
}

// Add smooth scrolling for internal links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Add loading state for action cards
function showLoadingState(element) {
    const originalContent = element.innerHTML;
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    // Simulate loading delay
    setTimeout(() => {
        element.innerHTML = originalContent;
    }, 1000);
}

// Error handling for charts
function handleChartError(error) {
    console.error('Chart initialization error:', error);
    const chartContainer = document.querySelector('.chart-container');
    if (chartContainer) {
        chartContainer.innerHTML = '<p class="text-center text-white">Error loading chart data</p>';
    }
}

// Initialize tooltips if Bootstrap is available
if (typeof bootstrap !== 'undefined') {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
