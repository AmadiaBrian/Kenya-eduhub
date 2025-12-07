// Global variable to store all resources
let allResources = [];

// Handle search from hero section
function searchResources() {
    const searchTerm = document.getElementById('heroSearchInput').value.trim();
    if (searchTerm) {
        // Set search term in the resource search input
        const resourceSearch = document.querySelector('.search-container input[type="text"]');
        if (resourceSearch) {
            resourceSearch.value = searchTerm;
        }
        // Filter resources
        filterResources(searchTerm);
        // Scroll to resources section
        document.getElementById('resourceList').scrollIntoView({ behavior: 'smooth' });
    }
}

// Filter resources based on search term
function filterResources(searchTerm = '') {
    if (!searchTerm && document.querySelector('.search-container input[type="text"]')) {
        searchTerm = document.querySelector('.search-container input[type="text"]').value.trim();
    }
    
    const filtered = allResources.filter(resource => {
        if (!searchTerm) return true;
        const searchLower = searchTerm.toLowerCase();
        return (
            (resource.title && resource.title.toLowerCase().includes(searchLower)) ||
            (resource.subject && resource.subject.toLowerCase().includes(searchLower)) ||
            (resource.level && resource.level.toLowerCase().includes(searchLower)) ||
            (resource.type && resource.type.toLowerCase().includes(searchLower)) ||
            (resource.description && resource.description.toLowerCase().includes(searchLower))
        );
    });
    
    displayResources(filtered);
}

// Highlight search terms in text
function highlightSearchTerm(text, term) {
    if (!term || !text) return text || '';
    try {
        const regex = new RegExp(`(${term})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    } catch (e) {
        return text;
    }
}

// Add event listeners for search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Hero search input
    const heroSearchInput = document.getElementById('heroSearchInput');
    if (heroSearchInput) {
        heroSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchResources();
            }
        });
    }

    // Hero search button
    const heroSearchBtn = document.getElementById('heroSearchBtn');
    if (heroSearchBtn) {
        heroSearchBtn.addEventListener('click', searchResources);
    }

    // Resource search input
    const resourceSearch = document.querySelector('.search-container input[type="text"]');
    if (resourceSearch) {
        resourceSearch.addEventListener('input', function() {
            filterResources(this.value.trim());
        });
    }
});

document.getElementById('resourceForm').addEventListener('submit', async (e) => {
    e.preventDefault();
  
    const formData = new FormData(e.target);
  
    const response = await fetch('upload.php', {
      method: 'POST',
      body: formData,
    });
  
    const result = await response.text();
    alert(result);
    e.target.reset();
    loadResources();
});
  
// Load resources from the server
async function loadResources() {
    try {
        const response = await fetch('fetch_resources.php');
        allResources = await response.json();
        
        // Get search term from URL or input field
        const urlParams = new URLSearchParams(window.location.search);
        let searchTerm = urlParams.get('q') || '';
        
        // Set search term in both search inputs if it exists in URL
        if (searchTerm) {
            const heroSearch = document.getElementById('heroSearchInput');
            const resourceSearch = document.querySelector('.search-container input[type="text"]');
            if (heroSearch) heroSearch.value = searchTerm;
            if (resourceSearch) resourceSearch.value = searchTerm;
        } else {
            // Get search term from input field if not in URL
            const resourceSearch = document.querySelector('.search-container input[type="text"]');
            searchTerm = resourceSearch ? resourceSearch.value.trim() : '';
        }
        
        // Filter and display resources
        filterResources(searchTerm);
    } catch (error) {
        console.error('Error loading resources:', error);
        const container = document.getElementById('resourceList');
        if (container) {
            container.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load resources. Please try again later.</p>
                </div>`;
        }
    }
}

// Display resources in the grid
function displayResources(resources) {
    const container = document.getElementById('resourceList');
    if (!container) return;
    
    // Get current search term for highlighting
    const searchTerm = document.querySelector('.search-container input[type="text"]')?.value.trim() || '';
    
    if (!resources || resources.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No resources found</h3>
                <p>Try adjusting your search or upload a new resource</p>
            </div>`;
        return;
    }
    
    container.innerHTML = resources.map(resource => {
        const icon = getFileIcon(resource.type);
        return `
            <div class="resource-card">
                <div class="card-header">
                    <h3>${icon} ${highlightSearchTerm(resource.title, searchTerm)}</h3>
                    <span class="badge">${resource.type ? resource.type.toUpperCase() : 'FILE'}</span>
                </div>
                <p><strong>Level:</strong> ${highlightSearchTerm(resource.level || 'N/A', searchTerm)}</p>
                <p><strong>Subject:</strong> ${highlightSearchTerm(resource.subject || 'N/A', searchTerm)}</p>
                <p class="description">${highlightSearchTerm(resource.description || 'No description available.', searchTerm)}</p>
                <a class="download-btn" href="uploads/${resource.filename}" download>
                    <i class="fas fa-download"></i> Download ${resource.type ? resource.type.toUpperCase() : 'File'}
                </a>
                <p class="timestamp"><em>Uploaded on: ${resource.created_at ? new Date(resource.created_at).toLocaleDateString() : 'N/A'}</em></p>
            </div>`;
    }).join('');
}

// Create search bar if it doesn't exist
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('searchBar')) {
        const searchInput = document.createElement('input');
        searchInput.placeholder = "🔍 Search by title, subject, or type...";
        searchInput.id = "searchBar";
        searchInput.style.cssText = "width:100%;padding:12px;margin:20px 0;border-radius:10px;border:1px solid #ccc;font-size:16px;";
        const resourceList = document.getElementById('resourceList');
        if (resourceList) {
            resourceList.parentNode.insertBefore(searchInput, resourceList);
            searchInput.addEventListener('input', function() {
                filterResources(this.value.trim());
            });
        }
    }

    // Initialize resources
    loadResources();

    // Dark mode toggle if the element exists
    const toggleMode = document.getElementById('toggleMode');
    if (toggleMode) {
        toggleMode.addEventListener("click", () => {
            document.documentElement.classList.toggle("dark");
        });
    }

    // Initialize testimonials slider if elements exist
    const testimonials = document.querySelectorAll('.testimonial');
    const dots = document.querySelectorAll('.dot');
    let currentTestimonial = 0;

    if (testimonials.length > 0) {
        function showTestimonial(index) {
            testimonials.forEach((test, i) => {
                test.classList.remove('active');
                if (dots[i]) dots[i].classList.remove('active');
                if (i === index) {
                    test.classList.add('active');
                    if (dots[i]) dots[i].classList.add('active');
                }
            });
        }

        const nextBtn = document.querySelector('.slider-next');
        const prevBtn = document.querySelector('.slider-prev');

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentTestimonial = (currentTestimonial + 1) % testimonials.length;
                showTestimonial(currentTestimonial);
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                currentTestimonial = (currentTestimonial - 1 + testimonials.length) % testimonials.length;
                showTestimonial(currentTestimonial);
            });
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentTestimonial = index;
                showTestimonial(index);
            });
        });

        // Auto-slide every 6 seconds
        const slideInterval = setInterval(() => {
            currentTestimonial = (currentTestimonial + 1) % testimonials.length;
            showTestimonial(currentTestimonial);
        }, 6000);

        // Initialize
        showTestimonial(0);
    }

    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Optional analytics for the button
    const whatsappBtn = document.querySelector('.whatsapp-contact-btn');
    if (whatsappBtn) {
        whatsappBtn.addEventListener('click', function() {
            console.log('WhatsApp contact button clicked');
            // Add any tracking code here
        });
    }
}); // End of DOMContentLoaded

// Helper function to get file icon based on file type
function getFileIcon(type) {
    if (!type) return '📄';
    type = type.toLowerCase();
    if (type.includes("pdf")) return "📕";
    if (type.includes("doc")) return "📝";
    if (type.includes("ppt")) return "📊";
    if (type.includes("xls") || type.includes("sheet")) return "📈";
    return "📄";
}
