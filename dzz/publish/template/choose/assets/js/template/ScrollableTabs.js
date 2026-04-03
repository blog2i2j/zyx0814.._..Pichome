class TabScrollController {
    constructor(options) {
      // 合并默认配置
      const defaults = {
        container: null,          // 必需 - 标签容器元素
        tabsWrapper: null,        // 必需 - 标签包裹元素
        prevButton: null,         // 必需 - 左按钮元素
        nextButton: null,         // 必需 - 右按钮元素
        disabledClass: 'disabled', // 禁用状态的class
        scrollOffset: 200,        // 每次滚动的距离
        checkOnInit: true,        // 初始化时检查状态
        checkOnResize: true       // 窗口大小变化时检查
      };
  
      this.config = { ...defaults, ...options };
  
      // 验证必需元素
      if (!this.config.container || !this.config.tabsWrapper || 
          !this.config.prevButton || !this.config.nextButton) {
        console.error('Missing required elements in configuration');
        return;
      }
  
      // 初始化
      if (this.config.checkOnInit) {
        this.checkScrollPosition();
      }
  
      // 绑定事件
      this.bindEvents();
    }
  
    bindEvents() {
      // 按钮点击事件
      this.config.prevButton.addEventListener('click', () => {
        this.scroll('left');
      });
  
      this.config.nextButton.addEventListener('click', () => {
        this.scroll('right');
      });
  
      // 标签容器滚动事件
      this.config.tabsWrapper.addEventListener('scroll', () => {
        this.checkScrollPosition();
      });
      console.log(this.config.checkOnResize);
      // 窗口大小变化事件
      if (this.config.checkOnResize) {
        window.addEventListener('resize', () => {
            console.log(11111);
          this.checkScrollPosition();
        });
      }
    }
  
    scroll(direction) {
      const currentScroll = this.config.tabsWrapper.scrollLeft;
      const scrollAmount = direction === 'left' 
        ? -this.config.scrollOffset 
        : this.config.scrollOffset;
  
      this.config.tabsWrapper.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
      });
    }
  
    checkScrollPosition() {
        const { container, tabsWrapper, prevButton, nextButton, disabledClass } = this.config;
        const scrollLeft = tabsWrapper.scrollLeft;
        const maxScroll = tabsWrapper.scrollWidth - tabsWrapper.clientWidth;
        if(tabsWrapper.scrollWidth > tabsWrapper.clientWidth){
            container.classList.remove(disabledClass);
            // 检查左按钮
            if (scrollLeft <= 0) {
                prevButton.classList.add(disabledClass);
            } else {
                prevButton.classList.remove(disabledClass);
            }

            // 检查右按钮
            if (scrollLeft >= maxScroll - 1) { // 减1避免小数精度问题
                nextButton.classList.add(disabledClass);
            } else {
                nextButton.classList.remove(disabledClass);
            }
        }else{
            container.classList.add(disabledClass);
        }
        
      
    }
  }