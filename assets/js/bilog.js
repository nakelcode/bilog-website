document.addEventListener("DOMContentLoaded", function () {

    const searchButton = document.querySelector(".search-bar button");
    const searchInput = document.querySelector(".search-bar input");

    if(searchButton){
        searchButton.addEventListener("click", function () {
            if (searchInput.value.trim() === "") {
                alert("Please enter a search term.");
            } else {
                alert("Searching for: " + searchInput.value);
            }
        });
    }

});

const hamburger = document.querySelector(".hamburger");
const navMenu = document.querySelector(".nav-menu");

hamburger.addEventListener("click", () => {

    hamburger.classList.toggle("active");
    navMenu.classList.toggle("active");
    document.body.classList.toggle("menu-open");

});

// ─── Nav Dropdown ───
function toggleDropdown() {
  document.getElementById('navUser').classList.toggle('open');
}
document.addEventListener('click', (e) => {
  const navUser = document.getElementById('navUser');
  if (navUser && !navUser.contains(e.target)) {
    navUser.classList.remove('open');
  }
});