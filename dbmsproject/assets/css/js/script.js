// Show password toggle
function togglePassword(id) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}

// Smooth scroll to top
document.addEventListener("DOMContentLoaded", () => {
    const toTop = document.createElement("div");
    toTop.innerHTML = "↑";
    toTop.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #00ffff;
        color: #000;
        font-size: 24px;
        padding: 10px;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        z-index: 1000;
    `;
    document.body.appendChild(toTop);

    toTop.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    window.addEventListener("scroll", () => {
        if (window.scrollY > 300) {
            toTop.style.display = "block";
        } else {
            toTop.style.display = "none";
        }
    });
});
