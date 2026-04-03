const MusicPlay = {
    name:'MusicPlay',
    template:`
        <div class="wavesurfer-box" ref="thumbnailWrap">
            <el-image style="width: 100%;height: 60px;" fit="fill" :src="waveImage?waveImage:data.icondata"></el-image>
            <div v-if="waveImage" class="progress-bar" :style="{width:thumbHandle.width}"></div>
            <div v-if="waveImage" class="progress-handle" :style="{left:thumbHandle.left}"></div>
        </div>
        <teleport to="body">
            <div class="playbar" v-loading="!data.finish" v-show="data.rid == playrid">
                <div class="left">
                    <div class="thumbnail" @click="playBtn">
                        <el-icon >
                            <template v-if="data.play">
                                <svg width="24" height="24" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 12V36" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M32 12V36" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </template>
                            <template v-else>
                                <svg width="24" height="24" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 24V11.8756L25.5 17.9378L36 24L25.5 30.0622L15 36.1244V24Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                            </template>
                        </el-icon>
                    </div>
                    <div class="name">{{data.name}}</div>
                </div>
                <div class="center">
                    <div class="time">{{progressTime}}</div>
                    <div class="wavesurfer-play" ref="DomMusicBox"></div>
                    <div class="time">{{data.fduration}}</div>
                </div>
                <div class="right">
                    <el-icon @click="ImageOperation('down')"><Download /></el-icon>
                    <el-icon @click="ImageOperation('share')"><Share /></el-icon>
                    <el-icon @click="ImageOperation('publish')"><Position /></el-icon>
                </div>
            </div> 
        </teleport>
        
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
        let DomMusicBox = ref(null);
        let waveImage = ref(null);
        let progressTime = ref(null);
        let wavesurfer = null;
        let thumbnailWrap = ref(null);
        let thumbHandle = reactive({
            width:'0%',
            left:'0%'
        });
        //创建
        function create(){
            wavesurfer = WaveSurfer.create({
                container: DomMusicBox.value,
                waveColor: '#888888',
                progressColor: 'purple',
                hideScrollbar:true,
                height:60,
                autoplay: true,
                url: props.data.mediaplayerpath,
                autoCenter:true,
                responsive: true,                // 响应式
                normalize: true,                 // 归一化波形
                backend: 'WebAudio'
            });

            
            wavesurfer.on("ready", async function(){
                try {
                    const fwaveImage = await wavesurfer.exportImage('image/png', {
                        width: '100%',
                        height: 60,
                        progress: true, // 不包含进度（进度通过进度条实现）
                        backgroundColor: 'purple'
                    });
                    waveImage.value = fwaveImage;
                    wavesurfer.play();
                    props.data.finish = true;
                } catch (error) {

                }
                
                
            });
            
            wavesurfer.on("audioprocess", async function(position){
                updateProgress(position);
            });
            wavesurfer.on("play", function(){
                props.data.play = true;
                context.emit('play',props.data.rid);
            });
            wavesurfer.on("pause", function(){
                props.data.play = false;
                context.emit('stop',props.data.rid);
            });
        }
        function updateProgress(position) {
            const currentTime = wavesurfer.getCurrentTime();
            const duration = parseInt(wavesurfer.getDuration());
            progressTime.value = ImageAudioPlaySecondToDate(parseInt(currentTime),duration);
            // 计算进度百分比
            const progressPercent = (position / duration) * 100;
            thumbHandle.width = `${progressPercent}%`;
            thumbHandle.left = `${progressPercent}%`;
        }
        function play(){
            if(!props.data.mediaplayerpath)return false;
            if(props.data.finish){
                wavesurfer.playPause();
            }else{
                create();   
            }
            
        }
        function pause(){
            if(props.data.finish && wavesurfer){
                wavesurfer.playPause();
            }
        }
        //时间计算
        function ImageAudioPlaySecondToDate(result,count){
            var h = Math.floor(result / 3600) < 10 ? '0'+Math.floor(result / 3600) : Math.floor(result / 3600);
            var m = Math.floor((result / 60 % 60)) < 10 ? '0' + Math.floor((result / 60 % 60)) : Math.floor((result / 60 % 60));
            var s = Math.floor((result % 60)) < 10 ? '0' + Math.floor((result % 60)) : Math.floor((result % 60));
            if(count >= 3600){
                return result = h + ":" + m + ":" + s;
            }else if(count >= 60){
                return result = m + ":" + s;
            }else{
                return result = s;
            }
        }
        // 5. 降级方案：手动绘制波形（如果exportImage失败）
        function fallbackWaveformDraw() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = '100%';
            canvas.height = 60;
            
            // 获取波形数据
            const peaks = wavesurfer.peaks(canvas.width);
            const centerY = canvas.height / 2;
            
            // 绘制波形
            ctx.fillStyle = 'purple';
            peaks.forEach((peak, i) => {
                const height = peak * canvas.height;
                ctx.fillRect(i, centerY - height/2, 1, height);
            });
            
            waveImage.value = canvas.toDataURL('image/png');
        }
        function playBtn(){
            if(props.data.finish && wavesurfer){
                wavesurfer.playPause();
            }
        };
        nextTick(() =>{
            thumbnailWrap.value.addEventListener('click', (e) => {
                if(!props.data.finish)return false;
                const rect = thumbnailWrap.value.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const percent = clickX / rect.width;
                const duration = wavesurfer.getDuration();
                const newPosition = duration * percent;
                
                // 更新进度并跳转播放位置
                wavesurfer.seekTo(percent);
                updateProgress(newPosition);
            });
        });
        function ImageOperation(type){
            if(type == 'down'){
                window.open(SITEURL+MOD_URL+'&op=download&dpath='+props.data.dpath);
            }else if(type == 'collect'){
          // self.$refs.RefImageLayout.ImageQuickChangeCollect(data.resourcedata.rid)
            }else if(type == 'publish'){
                window.open('index.php?mod=publish&op=choose&ptype=1&value='+props.data.rid);
            }else if(type == 'share'){
                // self.shareDialog.visible=true;
                // self.shareDialog.filepaths=[data.resourcedata.rid];
                // self.shareDialog.stype=0;
                // self.shareDialog.title=data.resourcedata.name;
                context.emit('share',{type:type,resourcedata:props.data});
            }
        }
        return {
            DomMusicBox,
            waveImage,
            progressTime,
            thumbHandle,
            thumbnailWrap,
            play,
            pause,
            playBtn,
            ImageOperation
            
        }
    }
};