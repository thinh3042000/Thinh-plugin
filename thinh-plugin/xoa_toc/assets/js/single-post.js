document.addEventListener("DOMContentLoaded", function() {
    const tocToggle = document.getElementById("toc-toggle");
    const tocList = document.getElementById("toc-list");
    const tocLinks = document.querySelectorAll(".toc-item a, .toc-subitem a");

    tocToggle.addEventListener("click", function() {
        if (tocList.style.display === "none" || tocList.style.display === "") {
            tocList.style.display = "block";
            tocToggle.innerHTML = `<svg aria-hidden="true" class="e-font-icon-svg e-fas-chevron-up" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M207.029 130.524L12.686 324.868c-9.373 9.373-9.373 24.569 0 33.941l22.667 22.667c9.357 9.357 24.522 9.375 33.901.04L224 227.495l154.745 154.021c9.379 9.335 24.544 9.317 33.901-.04l22.667-22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 130.524c-9.373-9.372-24.569 9.372-33.942 0z"></path></svg>`;
        } else {
            tocList.style.display = "none";
            tocToggle.innerHTML = `<svg aria-hidden="true" class="e-font-icon-svg e-fas-chevron-down" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544 9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z"></path></svg>`;
        }
    });

    tocLinks.forEach(link => {
        link.addEventListener("click", function(e) {
            tocLinks.forEach(link => link.classList.remove("active"));
            e.target.classList.add("active");
        });
    });

    window.addEventListener("scroll", function() {
        let current = "";
        let minDistance = Infinity;

        tocLinks.forEach(link => {
            const section = document.querySelector(link.hash);
            const rect = section.getBoundingClientRect();
            const distance = Math.abs(rect.top);

            if (distance < minDistance) {
                minDistance = distance;
                current = link.hash;
            }
        });

        tocLinks.forEach(link => {
            link.classList.remove("active");
            const parent = link.closest(".toc-item");
            if (link.hash === current) {
                link.classList.add("active");
                if (parent) {
                    parent.classList.add("active");
                    parent.querySelectorAll(".toc-sublist").forEach(sublist => sublist.style.display = "block");
                }
            } else {
                if (parent) {
                    parent.classList.remove("active");
                    parent.querySelectorAll(".toc-sublist").forEach(sublist => sublist.style.display = "none");
                }
            }
        });

        const tocItems = document.querySelectorAll(".toc-item");
        tocItems.forEach(tocItem => {
            const activeLink = tocItem.querySelector("a.link-item-custom.active, a.link-item-custom-child.active");

            if (activeLink) {
                tocItem.querySelectorAll(".toc-sublist").forEach(sublist => sublist.style.display = "block");
            } else {
                tocItem.querySelectorAll(".toc-sublist").forEach(sublist => sublist.style.display = "none");
            }
        });
    });
});