class FullScreenSlider {
    constructor(options) {
      // 默认配置
      const defaults = {
        interval: 5000, // 默认5秒
        autoPlay: true,
        videoAutoPlay: true,
        fadeDuration: 1000, // 渐变持续时间(ms)
        scaleEffect: false // 是否启用缩放效果
      };
      
      // 合并配置
      this.config = Object.assign({}, defaults, options);
      
      // 验证容器
      if (!this.config.container) {
        throw new Error('Container element is required');
      }
      
      // 获取DOM元素
      this.sliderContainer = this.config.container.querySelector('.slider-container');
      this.slides = Array.from(this.sliderContainer.querySelectorAll('.slider-slide'));
      this.paginationContainer = this.config.container.querySelector('.slider-pagination');
      this.dots = this.paginationContainer ? Array.from(this.paginationContainer.querySelectorAll('.slider-dot')) : [];
      
      // 检查是否有幻灯片
      if (this.slides.length === 0) {
        throw new Error('No slides found');
      }
      
      // 收集视频元素并设置初始状态
      this.videos = this.slides.map(slide => {
        const video = slide.querySelector('.slider-video');
        if (video) {
          video.muted = true; // 确保静音
          video.playsInline = true; // 移动端内联播放
        }
        return video;
      });
      
      // 初始化状态
      this.currentIndex = 0;
      this.timer = null;
      this.isSliding = false;
      
      // 初始化
      this.init();
    }
  
    init() {
    //   this.setupInitialStyles();
      
      // 单张幻灯片处理
      if (this.slides.length === 1) {
        if (this.paginationContainer) {
          this.paginationContainer.style.display = 'none';
        }
        this.playCurrentMedia();
        return;
      }
      
      // 初始化分页器
      this.initPagination();
      
      // 绑定事件
      this.bindEvents();
      
      // 开始自动播放
      if (this.config.autoPlay) {
        this.startAutoPlay();
      }
      
      // 播放当前媒体
      this.playCurrentMedia();
    }
  
    setupInitialStyles() {

      

      
    }
  
    initPagination() {
      if (!this.paginationContainer) return;
      

      this.updatePagination();

      this.dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const index = parseInt(dot.getAttribute('data-index'));
            this.goToSlide(index);
        });
    });
 
    }
  
    playCurrentMedia() {
      if (!this.config.videoAutoPlay) return;
      
      const currentVideo = this.videos[this.currentIndex];
      if (currentVideo) {
        currentVideo.currentTime = 0;
        currentVideo.play().catch(e => {
          console.warn('Video autoplay prevented:', e);
        });
      }
    }
  
    pauseCurrentMedia() {
      const currentVideo = this.videos[this.currentIndex];
      if (currentVideo) {
        currentVideo.pause();
      }
    }
  
    startAutoPlay() {
      if (this.timer) clearInterval(this.timer);
      
      this.timer = setInterval(() => {
        this.nextSlide();
      }, this.config.interval);
    }
  
    bindEvents() {
      // 鼠标交互
      this.config.container.addEventListener('mouseenter', () => {
        if (this.timer) {
          clearInterval(this.timer);
          this.timer = null;
        }
        this.pauseCurrentMedia();
      });
      
      this.config.container.addEventListener('mouseleave', () => {
        if (!this.timer && this.config.autoPlay) {
          this.startAutoPlay();
        }
        this.playCurrentMedia();
      });
      
      // 触摸事件（移动端）
      this.config.container.addEventListener('touchstart', () => {
        if (this.timer) {
          clearInterval(this.timer);
          this.timer = null;
        }
      });
      
      this.config.container.addEventListener('touchend', () => {
        if (!this.timer && this.config.autoPlay) {
          this.startAutoPlay();
        }
      });
    }
  
    nextSlide() {
      if (this.isSliding || this.slides.length <= 1) return;
      const nextIndex = (this.currentIndex + 1) % this.slides.length;
      this.goToSlide(nextIndex);
    }
  
    prevSlide() {
      if (this.isSliding || this.slides.length <= 1) return;
      const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
      this.goToSlide(prevIndex);
    }
  
    goToSlide(index) {
      if (this.isSliding || index === this.currentIndex || this.slides.length <= 1) return;
      
      this.isSliding = true;
      
      // 暂停当前媒体
      this.pauseCurrentMedia();
      
      // 淡出当前幻灯片
      const currentSlide = this.slides[this.currentIndex];
      currentSlide.style.opacity = '0';
      if (this.config.scaleEffect) {
        currentSlide.style.transform = 'scale(1.05)';
      }
      
      // 淡入新幻灯片
      const nextSlide = this.slides[index];
      nextSlide.style.opacity = '1';
      if (this.config.scaleEffect) {
        nextSlide.style.transform = 'scale(1)';
      }
      
      // 播放新媒体的内容
      this.currentIndex = index;
      this.playCurrentMedia();
      
      // 更新分页器
      this.updatePagination();
      
      // 重置自动轮播计时器
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
        if (this.config.autoPlay) {
          this.startAutoPlay();
        }
      }
      
      // 动画结束后重置状态
      setTimeout(() => {
        this.isSliding = false;
      }, this.config.fadeDuration);
    }
  
    updatePagination() {
      if (!this.paginationContainer || !this.dots || this.dots.length === 0) return;
      this.dots.forEach((dot, i) => {
        if (i === this.currentIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }
  
    destroy() {
      // 清除定时器
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
      
      // 停止所有视频
      this.videos.forEach(video => {
        if (video) {
          video.pause();
          video.currentTime = 0;
        }
      });
      
      // 移除事件监听
      this.config.container.removeEventListener('mouseenter', () => {});
      this.config.container.removeEventListener('mouseleave', () => {});
      this.config.container.removeEventListener('touchstart', () => {});
      this.config.container.removeEventListener('touchend', () => {});
      
      if (this.dots && this.dots.length > 0) {
        this.dots.forEach(dot => {
          dot.removeEventListener('click', () => {});
        });
      }
    }
  }
  
  // 使用示例
  /*
  document.addEventListener('DOMContentLoaded', function() {
    const sliderContainer = document.querySelector('.fullscreen-slider');
    const slider = new FullScreenSlider({
      container: sliderContainer,
      interval: 5000, // 视频幻灯片建议更长的间隔
      autoPlay: true,
      videoAutoPlay: true,
      fadeDuration: 1000,
      scaleEffect: true
    });
    
    // API控制
    // slider.nextSlide();
    // slider.prevSlide();
    // slider.goToSlide(1);
    // slider.destroy();
  });
  */