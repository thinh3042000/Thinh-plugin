// document.addEventListener("DOMContentLoaded", function() {
//     const tocToggle = document.getElementById("toc-toggle");
//     const tocList = document.getElementById("toc-list");
//     const tocLinks = document.querySelectorAll("#toc-list li a");

//     // Khởi tạo trạng thái ban đầu
//     let isTocVisible = true;

//     function updateTocVisibility() {
//         if (isTocVisible) {
//             tocList.style.display = "block";
//             tocToggle.innerHTML = `<svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path></svg>`;
//         } else {
//             tocList.style.display = "none";
//             tocToggle.innerHTML = `<svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544-9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z"></path></svg>`;
//         }
//     }

//     tocToggle.addEventListener("click", function() {
//         isTocVisible = !isTocVisible; // Đảo ngược trạng thái
//         updateTocVisibility();
//     });

//     // Cập nhật trạng thái ban đầu
//     updateTocVisibility();

//     tocLinks.forEach(link => {
//         link.addEventListener("click", function(e) {
//             tocLinks.forEach(link => link.classList.remove("active"));
//             e.target.classList.add("active");
//         });
//     });

//     window.addEventListener("scroll", function() {
//         let current = "";
//         let minDistance = Infinity;

//         tocLinks.forEach(link => {
//             const section = document.querySelector(link.hash);
//             if (section) {
//                 const rect = section.getBoundingClientRect();
//                 const distance = Math.abs(rect.top);

//                 if (distance < minDistance) {
//                     minDistance = distance;
//                     current = link.hash;
//                 }
//             }
//         });

//         // Highlight all links up to and including the current one
//         let highlightEnded = false;
//         tocLinks.forEach(link => {
//             if (!highlightEnded) {
//                 link.classList.add("active");
//                 if (link.hash === current) {
//                     highlightEnded = true;
//                 }
//             } else {
//                 link.classList.remove("active");
//             }
//         });
//     });
// });
document.addEventListener("DOMContentLoaded", function() {
    const tocToggle = document.getElementById("toc-toggle");
    const tocList = document.getElementById("toc-list");
    const tocLinks = document.querySelectorAll("#toc-list li a");

    // Khởi tạo trạng thái ban đầu
    let isTocVisible = true;

    function updateTocVisibility() {
        if (isTocVisible) {
            tocList.style.display = "block";
            tocToggle.innerHTML = `<svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path></svg>`;
        } else {
            tocList.style.display = "none";
            tocToggle.innerHTML = `<svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544-9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z"></path></svg>`;
        }
    }

    tocToggle.addEventListener("click", function() {
        isTocVisible = !isTocVisible; // Đảo ngược trạng thái
        updateTocVisibility();
    });

    // Cập nhật trạng thái ban đầu
    updateTocVisibility();

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
            if (section) {
                const rect = section.getBoundingClientRect();
                const distance = Math.abs(rect.top);

                if (distance < minDistance) {
                    minDistance = distance;
                    current = link.hash;
                }
            }
        });

        // Chỉ đánh dấu active cho mục hiện tại
        tocLinks.forEach(link => {
            if (link.hash === current) {
                link.classList.add("active");
            } else {
                link.classList.remove("active");
            }
        });
    });
});