const MusicPlay = {
    name:'MusicPlay',
    template:`
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
                    <el-icon @click="ImageOperation('down')"><Download></Download></el-icon>
                    <el-icon @click="ImageOperation('share')"><Share></Share></el-icon>
                    <el-icon @click="ImageOperation('delete')"><Close /></el-icon>
                </div>
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
        let DomMusicBox = ref(null);
        let progressTime = ref(null);
        let wavesurfer = null;
        let loading = false
        //创建
        function init(){
            if(loading)return false;
            loading = true;
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
            });

            
            wavesurfer.on("ready", async function(){
                // wavesurfer.play();
                props.data.finish = true;
                loading = false;
            });
            
            wavesurfer.on("audioprocess", async function(position){
                updateProgress(position);
            });
            wavesurfer.on("play", function(){
                // props.data.play = true;
                context.emit('play',props.data.rid);
            });
            wavesurfer.on("pause", function(){
                // props.data.play = false;
                context.emit('fstop',props.data.rid);
            });
            wavesurfer.on("error", function(){
                console.log(6666);
            });
        }
        function updateProgress(position) {
            const currentTime = wavesurfer.getCurrentTime();
            const duration = parseInt(wavesurfer.getDuration());
            progressTime.value = ImageAudioPlaySecondToDate(parseInt(currentTime),duration);
        }
        function play(){
            if(!props.data.mediaplayerpath || !props.data.finish || loading)return false;
            if(props.data.finish){
                wavesurfer.playPause();
            }else{
                init();
            }
            
        }
        function pause(){
            if(props.data.finish && wavesurfer){
                wavesurfer.stop();
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

        function playBtn(){
            if(props.data.finish && wavesurfer){
                wavesurfer.playPause();
            }
        };
        nextTick(() =>{
            // thumbnailWrap.value.addEventListener('click', (e) => {
            //     if(!props.data.finish)return false;
            //     const rect = thumbnailWrap.value.getBoundingClientRect();
            //     const clickX = e.clientX - rect.left;
            //     const percent = clickX / rect.width;
            //     const duration = wavesurfer.getDuration();
            //     const newPosition = duration * percent;
                
            //     // 更新进度并跳转播放位置
            //     wavesurfer.seekTo(percent);
            //     updateProgress(newPosition);
            // });
        });
        function ImageOperation(type){
            if(type == 'down'){
                window.open(SITEURL+MOD_URL+'&op=download&dpath='+props.data.dpath);
            }else if(type == 'collect'){
          // self.$refs.RefImageLayout.ImageQuickChangeCollect(data.resourcedata.rid)
            }else if(type == 'share'){
                context.emit('operation',{type:type,resourcedata:props.data});
            }else if(type == 'delete'){
                context.emit('operation',{type:type});
            }
        }
        return {
            DomMusicBox,
            progressTime,
            play,
            pause,
            playBtn,
            ImageOperation,
            init
            
        }
    }
};