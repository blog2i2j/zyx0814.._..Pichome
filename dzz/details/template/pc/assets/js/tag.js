const tem_tag = {
	props:{
		flag:{
			required:true,
			type: String,
			default:'tag',
		},
        dpath:{
			required:true,
			type: String,
			default:'',
		},
        appid:{
			required:true,
			type: String,
			default:'',
		},
        value:{
			required:true,
			type: Array,
			default:[],
		},
        lang:{
            required:true,
            type: Array,
            default:[],
        }
	},
	template: `
        <el-popover
            placement="left"
            ref="RightTagPopverRef"
            @before-enter="RightTagPopverShow"
            width="auto"
            trigger="click">
            <div style="height: 400px" v-loading="RightTagPopver.loading">
                <el-tabs 
                    v-model="RightTagPopver.leftactive"
                    @tab-change="RightTagRightData"
                    class="right-tag-tabs" 
                    tab-position="left" 
                    style="height: 400px">
                    <el-tab-pane :label="lang.All_labels" name="all">
                        <div style="height: 44px;">
                            <el-input
                                v-model.trim="RightTagPopver.keyword" 
                                prefix-icon="Search" 
                                :placeholder="lang.search"
                                @keydown.enter="RightTagPopverSearchEnter"
                                @input="RightTagPopverSearch"
                                clearable></el-input>
                        </div>
                        <el-scrollbar height="356px">
                            <template v-if="RightTagPopver.keyword">
                                <el-check-tag
                                    v-if="!RightTagPopver.right.searchCongruent"
                                    @change="RightTagPopverSearchEnter">
                                    {lang creation}“{{RightTagPopver.keyword}}”{lang label}
                                    </el-check-tag>
                                <el-check-tag
                                    v-for="val in RightTagPopver.right.searchdata"
                                    :checked="RightTagPopver.right.active.indexOf(val.tid)>-1"
                                    @change="RightTagPopverChange(val,key)"
                                    :key="val.tid">
                                    {{ val.tagname }}
                                    </el-check-tag>
                            </template>				
                            <template v-else>
                                <div v-if="RightTagPopver.right.recent.length" style="margin-bottom: var(--el-popover-padding);">
                                    <p style="padding-bottom: var(--el-popover-padding);">{lang Common_tags}</p>
                                    <el-check-tag 
                                        v-for="val in RightTagPopver.right.recent"
                                        :checked="RightTagPopver.right.active.indexOf(val.tid)>-1"
                                        @change="RightTagPopverChange(val,key)"
                                        :key="val.tid">
                                        {{ val.tagname }}
                                        </el-check-tag>
                                </div>
                                <template v-for="fitem in RightTagPopver.right.data">
                                    <div style="margin-bottom: var(--el-popover-padding);">
                                        <p style="padding-bottom: var(--el-popover-padding);">{{fitem.text}}({{fitem.num}})</p>
                                        <el-check-tag 
                                            v-for="val in fitem.data"
                                            :checked="RightTagPopver.right.active.indexOf(val.tid)>-1"
                                            @change="RightTagPopverChange(val,key)"
                                            :key="val.tid">
                                            {{ val.tagname }}
                                        </el-check-tag>
                                    </div>
                                </template>
                            </template>
                            
                        </el-scrollbar>
                    </el-tab-pane>
                    <el-tab-pane 
                        v-for="fitem in RightTagPopver.left" 
                        :label="fitem.text"
                        :name="fitem.cid">
                        <div v-loading="RightTagPopver.right.loading" style="height: 400px;">
                            <p style="padding-bottom: var(--el-popover-padding);display: flex;align-items: center;">{{fitem.text}}({{fitem.num}})<el-icon style="margin-left: 8px;cursor: pointer;" @click="RightTagPopverAddTag"><Circle-Plus /></el-icon></p>
                            <el-scrollbar height="368px" v-if="!RightTagPopver.right.loading">
                                <el-check-tag
                                    v-for="val in RightTagPopver.right.data"
                                    :checked="RightTagPopver.right.active.indexOf(val.tid)>-1"
                                    @change="RightTagPopverChange(val)"
                                    :key="val.tid">
                                    {{ val.tagname }}
                                </el-check-tag>
                            </el-scrollbar>
                        </div>
                    </el-tab-pane>
                </el-tabs>
            </div>
            <template #reference>
                <el-icon class="edit"><Edit-Pen /></el-icon>
            </template>
        </el-popover>
	`,
	
	data: function() {
		return {
			RightTagPopver:{
                leftactive:'all',
                loading:true,
                keyword:'',
                left:[],
                right:{
                    loading:true,
                    recent:[],
                    data:[],
                    arr:[],
                    searchdata:[],
                    searchCongruent:false,
                    active:[]
                },



            }
		};
	},
	watch:{

	},
	created() {

	},
	methods:{
        RightTagPopverSearch(val){
            const self = this;
            this.RightTagPopver.right.searchdata = [];
            this.RightTagPopver.right.searchCongruent = true;
            let Congruent = false;
            if(val &&  this.RightTagPopver.right.arr.length){
                let countData = this.RightTagPopver.right.arr.filter(item => {
                    if(item.tagname){
                        if(item.tagname == val){
                            Congruent = true;
                        }
                        return item.tagname.indexOf(val)>-1;
                    }
                });
                this.RightTagPopver.right.searchdata = countData;
            }
            self.RightTagPopver.right.searchCongruent = Congruent;
            
        },
        async RightTagPopverChange(data,key){//标签点击
            var self = this;
            var txtDel = '';
            var index = this.RightTagPopver.right.active.indexOf(data.tid);
            if(index>-1){
                this.RightTagPopver.right.active.splice(index,1);
            }else{
                this.RightTagPopver.right.active.push(data.tid);
            }
            // if(item.flag == 'lefttreetag'){
            //     let currIndex = item.data.findIndex(function(current,index){return current.tid == data.tid})
            //     if(currIndex > -1){
            //         item.data.splice(currIndex,1);
            //     }else{
            //         item.data.push(data);
            //     }
            //     item.value = JSON.parse(JSON.stringify(this.RightTagPopver.right.active));
            //     return false;
            // }

            let param;
            
            if(this.RightType == 'folder'){
                 param = {
                    flag:this.flag,
                    val:this.RightTagPopver.right.active.join(','),
                    fid:this.RightActivefid.join(','),
                    appid:this.appid,
                    path:this.dpath
                };
            }else{
                 param = {
                    flag:this.flag,
                    val:this.RightTagPopver.right.active.join(','),
                    appid:this.appid,
                    path:this.dpath
                };
            }
             let {data: res} = await axios.post(MOD_URL+'&op=fileinterface&operation=save',param);
            //let {data: res} = await axios.post('index.php?mod=pichome&op=library&do=lable&operation=save',param);
            if(res.success){
                let text = [];
                let val = [];
                for (let findex = 0; findex < res.data.tag.length; findex++) {
                    const element = res.data.tag[findex];
                    text.push(element.tagname);
                    val.push(element.tid);
                }

                
			    self.$emit('change',{
                    flag:this.flag,
                    data:res.data.tag
                });
                // this.value = val;
            }else{
                self.$message.error(res.msg || '{lang do_failed}');
            }
        },
        async RightTagPopverSearchEnter(){
            var self = this;
            if(!this.RightTagPopver.right.searchCongruent && this.RightTagPopver.keyword){
                if(!this.RightTagPopver.right.searchCongruent){
                    let url = MOD_URL+'&op=fileinterface&operation=label_add';
                    let {data: res} = await axios.post(MOD_URL+'&op=fileinterface&operation=label_add',{
                        appid:self.appid,
                        tags:this.RightTagPopver.keyword,
                        path:this.dpath
                        
                    });res.data.tag
                    if(res.success){
                        for (let index = 0; index < res.data.length; index++) {
                            const element = res.data[index];
                            element['tid'] = element.tid + '';
                            let domtag = document.querySelector('.tagcontent'+element.tid);
                            if(domtag){
                                self.$message.error('{lang Label_duplication}');
                                continue;
                            }
                            this.RightTagPopver.right.arr.push(element);
                            var curr = this.RightTagPopver.right.data.find(function(current){
                                return current.text == element.initial;
                            });
                            if(curr){
                                curr.data.push(element);
                            }
                            // if(item.flag == 'lefttreetag'){
                            //     item.value.push(element.tid);
                            //     item.data.push(element);
                            //     this.RightTagPopver.right.active.push(element.tid);
                            // }else{
                                this.RightTagPopverChange(element);
                            // }
                            
                        }
                        this.RightTagPopver.right.searchdata = [];
                        this.RightTagPopver.right.searchCongruent = false;
                        this.RightTagPopver.keyword = '';
                    
                    }else{
                        self.$message.error(data.msg || '{lang add_unsuccess}');
                    }
                }
            }else{
                if(this.RightTagPopver.right.searchdata.length){
                    this.RightTagPopverChange(this.RightTagPopver.right.searchdata[0]);
                    this.RightTagPopver.right.searchdata = [];
                    this.RightTagPopver.right.searchCongruent = false;
                    this.RightTagPopver.keyword = '';
                }
            }
        },
        RightTagPopverAddTag(data){
            var self = this;
   
            let dom = self.$refs['RightTagPopverRef'].popperRef.contentRef;
            
            self.$messageBox.prompt('', '{lang add_tag}', {
                confirmButtonText: '{lang confirms}',
                cancelButtonText: '{lang cancel}',
                inputPattern:/\S/,
                inputErrorMessage: '{lang name_cannot_empty}',
                appendTo:dom,
                validator: (value) => {
                    return !!value.trim();
                } 
            }).then(async ({ value }) => {
                let url = MOD_URL+'&op=fileinterface&operation=label_add';
                const {data: res} = await axios.post('index.php?mod=pichome&op=library&do=lable&operation=label_add',{
                    appid:self.appid,
                    tags:value,
                    cid:self.RightTagPopver.leftactive,
                    path:self.dpath
                });
                if(res.success){
                    let fdata = res.data;
                    for (let index = 0; index < fdata.length; index++) {
                        const element = fdata[index];
                        element['tid'] = element.tid+'';
                        
                        let domtag = document.querySelector('.tagcontent'+element.tid);
                        if(domtag){
                            self.$message.error('{lang Label_duplication}');
                            continue;
                        }
                        console.log(element);
                        let findex = self.RightTagPopver.right.data.findIndex(function(current){
                            return parseInt(current.tid) == parseInt(element.tid);
                        });
                        if(findex < 0){
                            self.RightTagPopver.right.data.push(element);
                            let curr = self.RightTagPopver.left.find(function(current){
                                return current.cid == self.RightTagPopver.leftactive;
                            });
                            if(curr)curr.num = parseInt(curr.num) + 1;
                        }
                    }
                }else{
                    self.$message.error(data.msg || '{lang add_unsuccess}');
                }
            }).catch(() => {
        
            })
        },
        async RightTagPopverShow(){
            let self = this;
            self.RightTagPopver = {
                leftactive:'all',
                loading:true,
                keyword:'',
                left:[],
                right:{
                    loading:true,
                    recent:[],
                    data:[],
                    arr:[],
                    searchdata:[],
                    searchCongruent:false,
                    active:[]
                },
            };
            // let res = await axios.post(MOD_URL+'&op=fileinterface&operation=label_popbox',{
            //     appid:self.appid,
            //     path:this.dpath
            // });
            let res = await axios.post('index.php?mod=pichome&op=library&do=lable&operation=label_popbox',{
                appid:self.appid,
                path:self.dpath
            });
            
            this.RightTagRightData();
            let data = res.data;
            if(data.success){
                self.RightTagPopver.left = data.arr;
            }else{
                self.$message.error(data.msg || '{lang get_data_fail}');
            }
            self.RightTagPopver.loading = false;
        },
        async RightTagRightData(){
            let self = this;
            self.RightTagPopver.right.loading = true;
            let tids = [];

            for(var j in this.value){
                tids.push(this.value[j].tid)
            }
            self.RightTagPopver.right.active = tids;
            let param = {
                appid:this.appid,
                tids:tids.join(','),
                path:this.dpath
            }
            if(this.RightTagPopver.leftactive != 'all'){
                param['cid'] = this.RightTagPopver.leftactive
            }
            // let res = await axios.post(MOD_URL+'&op=fileinterface&operation=getRigehtdata',param);
            let res = await axios.post('index.php?mod=pichome&op=library&do=lable&operation=getRigehtdata',param);
            let data = res.data;
            if(data.success){
                if(this.RightTagPopver.leftactive == 'all'){
                    
                    let arr = [];
                    let recent = [];
                    let fdata = [];
                    for(var i in data.arr){
                        arr.push(data.arr[i]);
                    }
                    this.RightTagPopver.right.arr = arr;
                    for(var x in data.recent){
                        recent.push(data.recent[x]);
                    }
                    this.RightTagPopver.right.recent = recent;
                    for(var t in data.data){
                        let item  = data.data[t];
                        var str = {
                            text:t,
                            num:0,
                            data:[]
                        }
                        for(var b in item){
                            str.data.push(item[b]);
                        }
                        str.num = str.data.length;
                        fdata.push(str);
                    }
                    this.RightTagPopver.right.data = fdata;
                }else{
                    this.RightTagPopver.right.data = data.arr;
                }
                self.RightTagPopver.right.loading = false;
            }else{
                self.$message.error(data.msg || '{lang get_data_fail}');
            }
            
        },
        
    },
	mounted() {
	},
	beforeRouteLeave() {
		
	},
};
