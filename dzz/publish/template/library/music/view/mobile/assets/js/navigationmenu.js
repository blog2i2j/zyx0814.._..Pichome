const navigationmenu = {
    name:'navigationmenu',
    template:`
        <template v-if="issub">
             <template v-for="item in datalist">
                    <template v-if="item.children && item.children.length">
                        <el-sub-menu 
                            :class="{'is-active':actives.findIndex(v=>v==item.id)>-1}"
                            :index="item.id">
                            <template #title>
                                <el-image v-if="item.icon" class="icon" :src="item.icon" fit="cover">
                                    <template #error><div class="el-image__placeholder"></div></template>
                                </el-image>
                                {{item.bannername}}
                                 <template  v-if="parseInt(item.btype) != 5">
                                       <el-link class="title-text" :href="item.url" :underline="false" @click.stop="parseInt(item.btype) != 5 && handleSelect(item)"  ></el-link>
                                </template>
                                <template v-else>
                                    <div class="title-text"></div>
                                </template>
                            </template>
                            <navigationmenu 
                                :datalists="item.children"
                                @handleselect="handleSelect"
                                :issub="true"
                                :actives="actives"></navigationmenu>
                        </el-sub-menu>
                    </template>
                    <template v-else>
                        <el-menu-item 
                            :index="item.id"
                            :disabled="parseInt(item.btype) == 3"
                            :class="{'is-active':actives.findIndex(v => v==item.id)>-1}">
                            <template #title>
                                <el-image v-if="item.icon" class="icon" :src="item.icon" fit="cover">
                                    <template #error><div class="el-image__placeholder"></div></template>
                                </el-image>
                                {{item.bannername}}
                                 <template  v-if="parseInt(item.btype) != 5">
                                       <el-link class="title-text" :href="item.url" :underline="false" @click.stop="parseInt(item.btype) != 5 && handleSelect(item)"  ></el-link>
                                </template>
                                <template v-else>
                                    <div class="title-text"></div>
                                </template>
                            </template>
                        </el-menu-item>
                    </template>
                </template>
            </template>
        <template v-else>
            <el-menu
                class="dzz-menu"
                :default-active=""
                mode="horizontal"
                :collapse-transition="false"
                style="border:0;height: 100%;max-width: 100%;">
                <template v-for="item in datalist">
                    <template v-if="item.children && item.children.length">
                        <el-sub-menu 
                            :class="{'is-active':actives.findIndex(v=>v==item.id)>-1}"
                            :index="item.id">
                            <template #title>
                                <el-image v-if="item.icon" class="icon" :src="item.icon" fit="cover">
                                    <template #error><div class="el-image__placeholder"></div></template>
                                </el-image>
                                {{item.bannername}}
                                 <template  v-if="parseInt(item.btype) != 5">
                                       <el-link class="title-text" :href="item.url" :underline="false" @click.stop="parseInt(item.btype) != 5 && handleSelect(item)"  ></el-link>
                                </template>
                                <template v-else>
                                    <div class="title-text"></div>
                                </template>
                            </template>
                            <navigationmenu 
                                :datalists="item.children"
                                @handleselect="handleSelect"
                                :issub="true"
                                :actives="actives"></navigationmenu>
                        </el-sub-menu>
                    </template>
                    <template v-else>
                        <el-menu-item 
                            :index="item.id"
                            :disabled="parseInt(item.btype) == 3"
                            :class="{'is-active':actives.findIndex(v => v==item.id)>-1}">
                            <template #title>
                                <el-image v-if="item.icon" class="icon" :src="item.icon" fit="cover">
                                    <template #error><div class="el-image__placeholder"></div></template>
                                </el-image>
                                {{item.bannername}}
                                 <template  v-if="parseInt(item.btype) != 5">
                                       <el-link class="title-text" :href="item.url" :underline="false" @click.stop="parseInt(item.btype) != 5 && handleSelect(item)"  ></el-link>
                                </template>
                                <template v-else>
                                    <div class="title-text"></div>
                                </template>
                            </template>
                        </el-menu-item>
                    </template>
                </template>
            </el-menu>
        </template>
    `,
    props: {

        datalists:{
            required:false,
            type: Array,
            default:[]
        },
        actives:{
            required:false,
            type: Array,
            default:[]
        },
        position:{
            required:true,
            type: String,
            default:'top'
        },
        dataurl:{
            required:false,
            type: String,
            default:''
        },
        issub:{
            required:false,
            type: Boolean,
            default:false
        }
    },
    setup(props, context){
       let bannerlist = ref({});
       let datalist = ref({});
       let actives = ref([]);
       if(props.datalists){
           datalist.value = props.datalists;
       }
       if(props.actives.length>0){
           actives.value = props.actives;
       }
       console.log(11111);
        async function getBannerData(){
            
            if(props.dataurl=='') return;
            const {data: res} = await axios.get(props.dataurl);
            if(res.success) {
                bannerlist.value = res.data.bannerlist;
            }else{
                bannerlist.value={
                    top:[],
                    bottom:[],
                    active:[]
                };
            }

            datalist.value = bannerlist.value[props.position];
            actives.value = bannerlist.value.active;
        }
        function handleSelect(key){
            context.emit('handleselect',key);
        }
        onMounted(() => {
            getBannerData();
        });

        return {
            datalist,
            actives,
            getBannerData,
            handleSelect
        };
    }
};