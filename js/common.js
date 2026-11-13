// =========================
// ヘッダーの表示・非表示
// =========================

const header = document.querySelector(".header");

let lastScrollY = window.scrollY;

// スクロール時の処理
window.addEventListener("scroll", () => {

    const currentScrollY = window.scrollY;

    // 下にスクロールした場合                            　↓これいじればヘッダーが消えるタイミングを変えられる
    if (currentScrollY > lastScrollY && currentScrollY > 100) {
        header.classList.add("hide");
    }

    // 上にスクロールした場合
    else if (currentScrollY < lastScrollY) {
        header.classList.remove("hide");
    }

    lastScrollY = currentScrollY;
});