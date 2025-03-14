class PageLoader {
    constructor() {
        this.currentPage = null;
        this.loadedResources = {
            css: null,
            js: null
        };
    }

    async loadPage(pagePath) {
        try {
            // 加载HTML
            const html = await fetch(`pages/${pagePath}.html`).then(r => r.text());
            document.getElementById('contentContainer').innerHTML = html;

            // 清理旧资源
            this.cleanupResources();

            // 加载CSS
            this.loadCSS(`pages/${pagePath}.css`);

            // 加载JS
            await this.loadJS(`pages/${pagePath}.js`);

        } catch (error) {
            console.error('页面加载失败:', error);
        }
    }

    loadCSS(path) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = path + '?t=' + Date.now();
        document.head.appendChild(link);
        this.loadedResources.css = link;
    }

    loadJS(path) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = path + '?t=' + Date.now();
            script.onload = resolve;
            script.onerror = reject;
            document.body.appendChild(script);
            this.loadedResources.js = script;
        });
    }

    cleanupResources() {
        if (this.loadedResources.css) {
            this.loadedResources.css.remove();
        }
        if (this.loadedResources.js) {
            this.loadedResources.js.remove();
        }
    }
}

// 初始化页面加载器
const pageLoader = new PageLoader();

// 绑定菜单点击事件
document.querySelectorAll('.menu a').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const pagePath = e.target.dataset.page;
        history.pushState(null, '', `#${pagePath}`);
        pageLoader.loadPage(pagePath);
    });
});

var show="none";
$(".Onemenu").each(function(){
    $(this).children(".submenu").hide();
})

$(".menutitle").click(function(){
    var tmp=$(this).parent(".Onemenu").children(".submenu");
    if(show=="none"){
        tmp.show();
        show="yes";
    }else{
        tmp.hide();
        show="none";
    }
})



// 处理浏览器前进/后退
window.addEventListener('popstate', () => {
    const pagePath = window.location.hash.slice(1);
    pageLoader.loadPage(pagePath);
});

// 初始加载
window.addEventListener('DOMContentLoaded', () => {
    const defaultPage = window.location.hash.slice(1) || 'dashboard/dashboard';
    pageLoader.loadPage(defaultPage);
});