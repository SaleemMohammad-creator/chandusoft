
 const backToTopButton = document.getElementById('back-to-top');
    
    // Show the button when scrolling down 100px from the top
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            backToTopButton.style.display = 'block'; // Show the button
        } else {
            backToTopButton.style.display = 'none'; // Hide the button
        }
    });

    // Smooth scroll to the top when the button is clicked
    backToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth' // Smooth scroll to top
        });
    });


