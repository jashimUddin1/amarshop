const html = document.documentElement;
const btn = document.getElementById("themeToggle");

btn.addEventListener("click", () => {

    let theme = html.getAttribute("data-bs-theme");

    if(theme === "light"){
        html.setAttribute("data-bs-theme","dark");
        btn.innerHTML = '<i class="bi bi-sun"></i>';
    }else{
        html.setAttribute("data-bs-theme","light");
        btn.innerHTML = '<i class="bi bi-moon"></i>';
    }

});