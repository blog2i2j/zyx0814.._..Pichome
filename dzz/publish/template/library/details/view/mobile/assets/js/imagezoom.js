const imagezoom = {
    name:'imagezoom',
    template:`
        <div class="ImgScroll" ref="container">
            <img
                v-if="showPlaceholder"
                class="placeholder-img"
                :src="placeholdersrc"
            >
            <img
                ref="targetImg"
                class="target-img"
                :src="largesrc"
                style="display: none"
                @load="handleLargeImageLoad"
                @error="handleLargeImageError"
            >
        </div>
    `,
    props: {
        placeholdersrc: {
            type: String,
            required: true
        },
        largesrc: {
            type: String,
            required: true
        },
        minScale: {
            type: Number,
            default: 1
        },
        maxScale: {
            type: Number,
            default: 3
        }
    },
    setup(props, context){
        const showPlaceholder = ref(true); // 是否显示小图
        const targetImg = ref(null);
        const container = ref(null);
        let pinchZoomInstance = null;

        // 大图加载完成
        const handleLargeImageLoad = () => {
            // 隐藏小图
            showPlaceholder.value = false;
            // 显示大图
            targetImg.value.style.display = 'block';
            // 初始化 PinchZoom
            initPinchZoom();
        };

        // 大图加载失败兜底
        const handleLargeImageError = () => {
            // console.error('大图加载失败，保留小图');
        };

        // 初始化 PinchZoom
        const initPinchZoom = () => {
            if (!targetImg.value) return;
            
            // 销毁旧实例
            if (pinchZoomInstance && pinchZoomInstance.destroy) {
                pinchZoomInstance.destroy();
            }

            // 创建新实例
            pinchZoomInstance = new PinchZoom(targetImg.value, {
                minScale: props.minScale,
                maxScale: props.maxScale,
                draggableUnzoomed: false
            });
        };

        // 组件卸载时销毁实例
        onUnmounted(() => {
            if (pinchZoomInstance) {
                if(pinchZoomInstance.destroy){
                    pinchZoomInstance.destroy();
                }
                
                pinchZoomInstance = null;
            }
        });
        return {
            container,
            targetImg,
            showPlaceholder,
            handleLargeImageLoad,
            handleLargeImageError
        }
    }
};

