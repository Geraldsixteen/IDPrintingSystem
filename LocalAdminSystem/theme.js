// theme.js
function toggleMode() {
    document.body.classList.toggle("dark");
    let toggleBtn = document.querySelector(".toggle-mode");
    if (document.body.classList.contains("dark")) {
        toggleBtn.innerHTML = "☀️ Light Mode";
        localStorage.setItem("theme", "dark");
    } else {
        toggleBtn.innerHTML = "🌙 Dark Mode";
        localStorage.setItem("theme", "light");
    }
}

window.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.querySelector(".toggle-mode");
    if (!toggleBtn) return;

    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
        toggleBtn.innerHTML = "☀️ Light Mode";
    }
});
