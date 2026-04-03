const Tmpimage_mix = {
    props:{
        model:{
            required:true,
            type: Object,
            default:{},
        },
        field:{
            required:true,
            type: Object,
            default:{},
        },
        ParenIndex:{
            required:true,
            type: Number,
            default:0,
        },
        typecollection:{
            required:true,
            type: Object,
            default:{},
        },
        licenseversion: {
            required: true,
            type: Number,
            default:0
        }
    },   
    template:`
        <div >
            <el-form label-width="150px" label-suffix=":">
                <el-form-item :label="Lang.text9">
                    <el-upload
                        class="avatar-uploader search_rec-uploader"
                        style="overflow: unset;"
                        action="index.php?mod=alonepage&op=alonepageinterface&do=upload"
                        :show-file-list="false"
                        accept="image/gif,image/png,image/jpg,image/jpeg,image/svg"
                        name="files"
                        :on-success="handleUploadSucess">
                        <el-image 
                            v-if="model.data[0].data[0].img"
                            class="avatarimg" 
                            fit="contain" 
                            :src="model.data[0].data[0].img"></el-image>
                        <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
                        <el-icon class="delete" @click.stop="deleteimage" v-if="model.data[0].data[0].img"><Circle-Close-Filled /></el-icon>
                    </el-upload>
                </el-form-item>
                <el-form-item :label="Lang.text1">
                    <el-input v-model="model.data[0].data[0].title" style="width:50%;" clearable />
                </el-form-item>
                <el-form-item :label="Lang.text2">
                    <el-input  
                        v-model="model.data[0].data[0].desc" 
                        style="width:50%;" 
                        :autosize="{ minRows: 2, maxRows: 6 }"
                        type="textarea"
                        clearable />
                </el-form-item>
                <el-form-item :label="Lang.text30">
                    <el-radio-group v-model="model.data[0].data[0].align">
                        <el-radio label="left" border>{{Lang.text31}}</el-radio>
                        <el-radio label="center" border>{{Lang.text32}}</el-radio>
                        <el-radio label="right" border>{{Lang.text33}}</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item :label="Lang.text18">
                    <div style="width: 100%;"> 
                        <div style="width: 100%;display:flex;min-width: 100%;">
                            <div style="display:flex;width:50%;margin-bottom: 6px;">
                                <el-input style="width: 130px;margin-right:6px;" v-model="model.data[0].data[0].moretxt" ></el-input>
                                <el-select v-model="model.data[0].data[0].link" style="width: 110px;margin-right:6px;" @change="model.data[0].data[0].linkval=''">
                                    <el-option :label="Lang.text26" value="0"></el-option>
                                    <el-option :label="Lang.text27" value="1"></el-option>
                                    <el-option :label="Lang.text28" value="2"></el-option>
                                    <el-option :label="Lang.text29" value="3"></el-option>
                                     <el-option v-if="licenseversion>1" :label="Lang.text34" value="4"></el-option>
                                     <el-option :label="Lang.text35" value="5"></el-option>
                                </el-select>
                                <template v-if="parseInt(model.data[0].data[0].link) == 0">
                                    <el-input v-model="model.data[0].data[0].linkval"></el-input>
                                </template>
                                <template v-else-if="parseInt(model.data[0].data[0].link) == 1">
                                    <el-select v-model="model.data[0].data[0].linkval" style="width: 100%">
                                        <el-option v-for="item in typecollection.library" :label="item.appname" :value="item.appid"></el-option>
                                    </el-select>
                                </template>
                                <template v-else-if="parseInt(model.data[0].data[0].link) == 2">
                                    <el-select v-model="model.data[0].data[0].linkval" style="width: 100%">
                                        <el-option v-for="item in typecollection.alonepage" :label="item.pagename" :value="item.id" :key="item.id"></el-option>
                                    </el-select>
                                </template>
                                <template v-else-if="licenseversion>1 && parseInt(model.data[0].data[0].link) == 4">
                                    <el-select v-model="model.data[0].data[0].linkval" style="width: 100%">
                                        <el-option v-for="item in typecollection.tab" :label="item.name" :value="item.gid" :key="item.gid"></el-option>
                                    </el-select>
                                </template>
                                <template v-else-if="parseInt(model.data[0].data[0].link) == 3">
                                    <el-cascader 
                                        style="width: 100%"
                                        v-model="model.data[0].data[0].linkval" 
                                        :options="typecollection.banner"
                                        :show-all-levels="false"
                                        :emitPath="false"
                                        :props="{value:'id',label:'bannername',checkStrictly:true}" 
                                        clearable></el-cascader>
                                </template>
                                <template v-else-if="parseInt(model.data[0].data[0].link) == 5">
                                        <el-select 
                                            v-model="model.data[0].data[0].linkval" 
                                            filterable
                                            remote
                                            :remote-method="getPublishList"
                                            :loading="DataLoading"
                                            style="width: 100%">
                                            <el-option v-for="item in DataList1" :label="item.name" :value="item.id" :key="item.id"></el-option>
                                        </el-select>
                                     </template>
                            </div>
              
                            
                        </div>
                            
                        <el-text size="small" tag="p" type="info">{{Lang.text24}}</el-text>
                    </div>
                </el-form-item>
            </el-form>
        </div>
    `,
    setup(props,context){
        let Lang = {
            text1:__lang.title,
            text2:__lang.desc,
            text3:__lang.backgroundcolor,
            text4:__lang.default_groupname,
            text5:__lang.Automatic_acquisition,
            text6:__lang.Manual_settings,
            text7:__lang.Specify_label_tip,
            text8:__lang.Hotword_settings,
            text9:__lang.photo,
            text18:__lang.tip3,
            text26:__lang.address,
            text27:__lang.library,
            text28:__lang.page,
            text29:__lang.column,
            text24:__lang.tip4,
            text30:__lang.alignment,
            text31:__lang.alignment_left,
            text32:__lang.alignment_center,
            text33:__lang.alignment_right,
            text34:__lang.album,
            text35:__lang.publish
        };

        function handleUploadSucess(response, file, fileList){//上传成功
            if(response.files && response.files.length){
                let files = response.files[0];
                if(files.error){
                    ElementPlus.ElMessage({message:files.error,type:'error'});
                }else if( files.data) {
                    props.model.data[0].data[0].aid = files.data.aid;
                    props.model.data[0].data[0].img = files.data.img;
                }
            }
        }
        function searchclassifyChange(data){
            let datas = [];
            if(data.length){
                for (let index = 0; index < data.length; index++) {
                    const element = data[index];
                    for (let findex = 0; findex < props.typecollection.search.length; findex++) {
                        const felement = props.typecollection.search[findex];
                        if(element == felement.id){
                            datas.push({
                                id: felement.id,
                                icon: felement.icon || '',
                                bannername: felement.bannername,
                                btype: felement.btype || '',
                                bdata: felement.bdata || '',
                                realurl: felement.realurl || '',
                                url: felement.url || '',
                                value:''
                            });
                        }
                    }
                }


                datas.forEach(element => {
                    let curr = props.model.data[0].data[0].hotsValue.find(function(current){
                        return current.id == element.id;
                    });
                    if(curr){
                        element.value = curr.value;
                    }
                });
                props.model.data[0].data[0].hotsValue = datas;
                let xindex = data.indexOf(props.model.data[0].data[0].defaultclassify);
                if(xindex < 0){
                    props.model.data[0].data[0].defaultclassify = data[0]+'';
                }
            }else{
                props.model.data[0].data[0].defaultclassify = ''
                props.model.data[0].data[0].hotsValue = [];
            }
        }
        function deleteimage(){
            props.model.data[0].data[0].aid = 0;
            props.model.data[0].data[0].img = '';
        }

        function deleteOptions(index){
            props.model.data[0].data[0].links.splice(index,1);
        }
        let DataList1 = ref([]);
        let DataLoading = ref(false);
        async function getPublishList(query){
            DataLoading.loading = true;
            const {data: res} = await axios.post(BasicUrl+'getPublishList',{q: query,ids:[props.model.data[0].data[0].linkval]});
            if(res.success){
                DataList1.value = res.data;
            }else{
                ElementPlus.ElMessage.error(res.msg || __lang.get_data_fail);
            }
            DataLoading.loading = false;
        }

        getPublishList();
        return {
            Lang,
            handleUploadSucess,
            searchclassifyChange,
            deleteimage,
            deleteOptions,
            DataList1,
            DataLoading,
            getPublishList
        };
    }
}