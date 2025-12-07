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
  
  async function loadResources() {
    const res = await fetch('fetch_resources.php');
    const data = await res.json();
    const list = document.getElementById('resourceList');
    list.innerHTML = '';
  
    data.forEach(item => {
      list.innerHTML += `
        <div class="resource-card">
          <div class="card-header">
            <h3>${item.title}</h3>
            <span class="badge">${item.type.toUpperCase()}</span>
          </div>
          <p><strong>Level:</strong> ${item.level}</p>
          <p><strong>Subject:</strong> ${item.subject}</p>
          <p class="description">${item.description}</p>
          <a class="download-btn" href="uploads/${item.filename}" download>
            📥 Download Document
          </a>
          <p class="timestamp"><em>Uploaded on: ${new Date(item.created_at).toLocaleDateString()}</em></p>
        </div>
      `;
    });
  }
  const searchInput = document.createElement('input');
searchInput.placeholder = "🔍 Search by title, subject, or type...";
searchInput.id = "searchBar";
searchInput.style.cssText = "width:100%;padding:12px;margin-bottom:25px;border-radius:10px;border:1px solid #ccc;font-size:16px;";
document.body.insertBefore(searchInput, document.getElementById("resourceList"));

searchInput.addEventListener('input', loadResources);

  loadResources();

  document.getElementById("toggleMode").addEventListener("click", () => {
    document.documentElement.classList.toggle("dark");
  });

  async function loadResources() {
    const res = await fetch('fetch_resources.php');
    const data = await res.json();
    const list = document.getElementById('resourceList');
    const query = document.getElementById('searchBar').value.toLowerCase();
    list.innerHTML = '';
  
    data.forEach(item => {
      const match = item.title.toLowerCase().includes(query) ||
                    item.subject.toLowerCase().includes(query) ||
                    item.type.toLowerCase().includes(query);
  
      if (!match) return;
  
      const icon = getFileIcon(item.type);
  
      list.innerHTML += `
        <div class="resource-card">
          <div class="card-header">
            <h3>${icon} ${item.title}</h3>
            <span class="badge">${item.type.toUpperCase()}</span>
          </div>
          <p><strong>Level:</strong> ${item.level}</p>
          <p><strong>Subject:</strong> ${item.subject}</p>
          <p class="description">${item.description}</p>
          <a class="download-btn" href="uploads/${item.filename}" download>
            📥 Download Document
          </a>
          <p class="timestamp"><em>Uploaded on: ${new Date(item.created_at).toLocaleDateString()}</em></p>
        </div>
      `;
    });
  }
  
  function getFileIcon(type) {
    type = type.toLowerCase();
    if (type.includes("pdf")) return "📕";
    if (type.includes("doc")) return "📝";
    if (type.includes("ppt")) return "📊";
    if (type.includes("xls") || type.includes("sheet")) return "📈";
    return "📄";
  }
  


  const testimonials = document.querySelectorAll('.testimonial');
const dots = document.querySelectorAll('.dot');
let currentTestimonial = 0;

function showTestimonial(index) {
  testimonials.forEach((test, i) => {
    test.classList.remove('active');
    dots[i].classList.remove('active');
    if (i === index) {
      test.classList.add('active');
      dots[i].classList.add('active');
    }
  });
}

document.querySelector('.slider-next').addEventListener('click', () => {
  currentTestimonial = (currentTestimonial + 1) % testimonials.length;
  showTestimonial(currentTestimonial);
});

document.querySelector('.slider-prev').addEventListener('click', () => {
  currentTestimonial = (currentTestimonial - 1 + testimonials.length) % testimonials.length;
  showTestimonial(currentTestimonial);
});

dots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    currentTestimonial = index;
    showTestimonial(index);
  });
});

// Auto-slide every 6 seconds
setInterval(() => {
  currentTestimonial = (currentTestimonial + 1) % testimonials.length;
  showTestimonial(currentTestimonial);
}, 6000);

// Initialize
showTestimonial(0);


document.querySelector('.hamburger').addEventListener('click', () => {
  document.querySelector('.nav-links').classList.toggle('active');
});


// Optional analytics for the button
document.querySelector('.whatsapp-contact-btn').addEventListener('click', function() {
  console.log('WhatsApp contact button clicked');
  // Add any tracking code here
});
  




