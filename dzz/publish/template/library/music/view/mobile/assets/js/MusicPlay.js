const MusicPlay = {
    name:'MusicPlay',
    template:`
            <div class="playbar" v-show="data.rid == playrid" v-loading="loading">
                <div class="message">
                    <div class="left">
                        <div class="thumbnail" @click="togglePlayPause()">
                            <el-icon >
                                <template v-if="isPlaying">
                                    <svg width="24" height="24" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 12V36" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M32 12V36" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </template>
                                <template v-else>
                                    <svg width="24" height="24" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 24V11.8756L25.5 17.9378L36 24L25.5 30.0622L15 36.1244V24Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                                </template>
                            </el-icon>
                        </div>
                        
                        </div>
                    <div class="center">
                        <el-text class="name" truncated>
                            {{data.name}}
                        </el-text>
                    </div>
                    <div class="right">
                        <el-icon @click.stop="ImageOperation('down')"><Download></Download></el-icon>
                        <el-icon @click.stop="ImageOperation('share')"><Share></Share></el-icon>
                        <el-icon @click.stop="ImageOperation('delete')"><Close /></el-icon>
                    </div>
                </div>
                <div class="progress">
                    <div class="time-start">
                        <el-text size="small" type="info">{{formatTime(currentTime)}}</el-text>
                    </div>
                    <div class="el-slider">
                        <div 
                            ref="container"
                            class="el-slider__runway"
                            @click="handleProgressClick"
                            @mousedown="startDrag"
                            @touchstart="startDrag">
                            <div class="el-slider__bar" :style="{ width: progressPercent+'%' }"></div>
                            <div 
                                class="el-slider__button-wrapper" 
                                :style="{ left: progressPercent+'%' }"
                                @mousedown.stop="startDrag"
                                @touchstart.stop="startDrag">
                                <div class="el-slider__button el-tooltip__trigger el-tooltip__trigger"></div>
                            </div>
                        </div>
                    </div>
                    <div class="time-end">
                        <el-text size="small" type="info">{{ formatTime(duration)}}</el-text>
                    </div>
                </div>
                <audio 
                    ref="audioRef"
                    hidden
                ></audio>
            </div>
    `,
    props: {
        data:{
            required:true,
            type: Object,
            default:{},
        },
        playrid:{
            required:false,
            type: String,
            default:'',
        },
    },
    setup(props, context){
       // 响应式数据
        const audioRef = ref(null);
        const container = ref(null);
        // 核心控制状态：是否已初始化
        const isInitialized = ref(false);
        const loading = ref(true);

        // 播放器状态
        const isPlaying = ref(false);
        const currentTime = ref(0);
        const duration = ref(0);
        const progressPercent = ref(0);
        const isDragging = ref(false);

        // ========== 核心：手动初始化函数 ==========
        const initPlayer = async () => {
            if (isInitialized.value) {
                togglePlayPause();
                console.log('播放器已初始化，无需重复操作');
                return;
            }
            if (!audioRef.value) {
                console.log('音频元素获取失败');
                return;
            }

            try {
                loading.value = true;
                // 1. 设置音频源
                audioRef.value.src = props.data.mediaplayerpath;
                // 2. 预加载音频元数据（获取时长等信息）
                await audioRef.value.load();

                // 3. 绑定音频事件（核心）
                bindAudioEvents();

                // 4. 标记为已初始化
                isInitialized.value = true;
                // statusText.value = '初始化成功，可点击播放';

                // 5. 可选：初始化后自动播放（浏览器大概率拦截）
                    await audioRef.value.play();
                    
            } catch (err) {
                // statusText.value = `初始化失败：${err.message}`;
                loading.value = false;
                console.error('播放器初始化失败：', err);
            }
        };


        // ========== 事件绑定/解绑 ==========
        const bindAudioEvents = () => {
            const audio = audioRef.value;
            if (!audio) return;

            // 绑定原生音频事件
            audio.addEventListener('play', handlePlay);
            audio.addEventListener('pause', handlePause);
            audio.addEventListener('timeupdate', handleTimeUpdate);
            audio.addEventListener('ended', handleEnded);
            audio.addEventListener('error', handleError);
            audio.addEventListener('loadedmetadata', handleLoadedMetadata);
        };
        onUnmounted(() => {
            destroyPlayer();
        });
        // ========== 核心：销毁播放器函数 ==========
        const destroyPlayer = () => {
            if (!isInitialized.value) {
            // statusText.value = '播放器未初始化，无需销毁';
                return;
            }
        
            // 1. 暂停播放
            if (audioRef.value) {
                audioRef.value.pause();
            }
            // 2. 移除所有事件监听
            unbindAudioEvents();
            // 3. 清空音频源和状态
            if (audioRef.value) {
            audioRef.value.src = ''; // 释放音频资源
            }
            isInitialized.value = false;
            isPlaying.value = false;
            props.data.play = false;
            currentTime.value = 0;
            duration.value = 0;
            progressPercent.value = 0;
            // 4. 清理拖动监听（防止内存泄漏）
            stopDrag();
            // 5. 更新状态
            // statusText.value = '播放器已销毁，可重新初始化';
        };
  
        const unbindAudioEvents = () => {
            const audio = audioRef.value;
            if (!audio) return;

            // 移除所有绑定的事件
            audio.removeEventListener('play', handlePlay);
            audio.removeEventListener('pause', handlePause);
            audio.removeEventListener('timeupdate', handleTimeUpdate);
            audio.removeEventListener('ended', handleEnded);
            audio.removeEventListener('error', handleError);
            audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
        };

        // ========== 播放/暂停控制 ==========
        const togglePlayPause = async (value) => {
            if (!isInitialized.value || !audioRef.value) return;
            try {
                if(value){
                    if(value == 2){
                        audioRef.value.pause();
                    }else{
                        audioRef.value.play();
                    }
                }else{
                    if (isPlaying.value) {
                        audioRef.value.pause();
                    } else {
                        await audioRef.value.play();
                    }
                }
                
            } catch (err) {
                // statusText.value = `播放失败：${err.message}（浏览器禁止自动播放）`;
            }
        };

        // ========== 进度条拖动/点击逻辑 ==========
        const startDrag = (e) => {
            if (!isInitialized.value || !duration.value) return;
            isDragging.value = true;
            document.addEventListener('mousemove', handleDrag);
            document.addEventListener('touchmove', handleDrag, { passive: false });
            document.addEventListener('mouseup', stopDrag);
            document.addEventListener('touchend', stopDrag);
            handleDrag(e);
        };

        const handleDrag = (e) => {
            if (!isDragging.value || !audioRef.value || !duration.value) return;

            let clientX;
            if (e.type.includes('touch')) {
                e.preventDefault();
                clientX = e.touches[0].clientX;
            } else {
                clientX = e.clientX;
            }

            const refcontainer = container.value;
            const containerRect = refcontainer.getBoundingClientRect();
            const containerWidth = containerRect.width;
            const offsetX = Math.max(0, Math.min(clientX - containerRect.left, containerWidth));
            const percent = (offsetX / containerWidth) * 100;
            const targetTime = (percent / 100) * duration.value;

            progressPercent.value = percent;
            currentTime.value = targetTime;
            audioRef.value.currentTime = targetTime;
        };

        const stopDrag = () => {
            if (!isDragging.value) return;
            isDragging.value = false;
            document.removeEventListener('mousemove', handleDrag);
            document.removeEventListener('touchmove', handleDrag);
            document.removeEventListener('mouseup', stopDrag);
            document.removeEventListener('touchend', stopDrag);

            // 拖动结束后自动播放（可选）
            if (isInitialized.value && audioRef.value?.paused) {
                audioRef.value.play().catch(() => {
                // statusText.value = '需手动点击播放按钮';
                });
            }
        };

        const handleProgressClick = (e) => {
            if (isDragging.value || !isInitialized.value || !duration.value) return;

            const refcontainer = e.currentTarget;
            const containerRect = refcontainer.getBoundingClientRect();
            const clickX = e.clientX - containerRect.left;
            const percent = (clickX / containerRect.width) * 100;
            const targetTime = (percent / 100) * duration.value;

            progressPercent.value = percent;
            currentTime.value = targetTime;
            audioRef.value.currentTime = targetTime;
        };

        // ========== 事件处理函数 ==========
        const handlePlay = () => {
            isPlaying.value = true;
            props.data.play = true;
            context.emit('musicplay',props.data.rid);
            // statusText.value = '正在播放';
        };

        const handlePause = () => {
            isPlaying.value = false;
            props.data.play = false;
            context.emit('musicstop',props.data.rid);
            // statusText.value = '已暂停';
        };

        const handleTimeUpdate = () => {
            if (isDragging.value || !audioRef.value) return;
            currentTime.value = audioRef.value.currentTime;
            progressPercent.value = duration.value ? (currentTime.value / duration.value) * 100 : 0;
        };

        const handleEnded = () => {
            isPlaying.value = false;
            currentTime.value = 0;
            progressPercent.value = 0;
            // statusText.value = '播放完毕';
            if (audioRef.value) audioRef.value.currentTime = 0;
        };

        const handleError = () => {
            loading.value = false;
            // statusText.value = '音乐加载失败，请检查文件路径';
        };

        const handleLoadedMetadata = () => {
            // console.log('加载中。。。');
            if (!audioRef.value) return;
            loading.value = false;
            duration.value = audioRef.value.duration || 0;
            // console.log('音频元数据加载完成，可播放');
            // statusText.value = '音频元数据加载完成，可播放';
        };

        // ========== 工具函数 ==========
        const formatTime = (seconds) => {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        };
        function ImageOperation(type){
            context.emit('operation',{
                type:type,
                value:props.data
            });
        }
        return {
            currentTime,
            duration,
            audioRef,
            isPlaying,
            progressPercent,
            container,
            loading,
            togglePlayPause,
            handleProgressClick,
            startDrag,
            formatTime,
            initPlayer,
            ImageOperation
        }
    }
};