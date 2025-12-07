// Optional: Fade in elements on scroll
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".feature-card");

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = 1;
        entry.target.style.transform = "translateY(0)";
      }
    });
  }, {
    threshold: 0.2
  });

  cards.forEach(card => {
    card.style.opacity = 0;
    card.style.transform = "translateY(30px)";
    observer.observe(card);
  });
});
