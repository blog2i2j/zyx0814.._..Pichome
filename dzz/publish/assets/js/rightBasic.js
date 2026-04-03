const rightformbasic = {
    props:{
        propdata:{
            required:true,
            type: Object,
            default:{},
        }
    },
    template:`
    <el-scrollbar>
        <el-form 
            :model="DataList" 
            label-position="top" 
            label-suffix=":"
            style="width: 100%;">
            <el-form-item label="页面标题">
                <el-input v-model="DataList.pname"></el-input>
            </el-form-item>
            <el-form-item label="页面描述">
                <el-input v-model="DataList.pdesc"></el-input>
            </el-form-item>
            <el-form-item label="链接">
                <div style="display: flex;width: 100%;">
                    <el-input v-model="DataList.address" style="flex: 1;"></el-input>
                    <!-- <el-button style="margin-left: 6px;" icon="CopyDocument"></el-button> -->
                </div>
            </el-form-item>
            <el-form-item label="浏览权限">
                <orguser-select 
                    defaulttype="view" 
                    @change="SettingUserChange" 
                    :defaultheckeds="SettingFormvisit.checked" 
                    :defaultexpanded="SettingFormvisit.expanded" 
                    :defaultdata="SettingFormvisit.data"></orguser-select>
            </el-form-item>
            <el-form-item label="下载权限">
                <orguser-select 
                    defaulttype="down" 
                    @change="SettingUserChange" 
                    :defaultheckeds="SettingFormdown.checked" 
                    :defaultexpanded="SettingFormdown.expanded" 
                    :defaultdata="SettingFormdown.data"></orguser-select>
            </el-form-item>
            <el-form-item label="分享权限">
                <orguser-select 
                    defaulttype="share" 
                    @change="SettingUserChange" 
                    :defaultheckeds="SettingFormshare.checked" 
                    :defaultexpanded="SettingFormshare.expanded" 
                    :defaultdata="SettingFormshare.data"></orguser-select>
            </el-form-item>
        </el-form>
    </el-scrollbar>

    `,
    setup(props, context){
        let DataList = reactive({
            pname:'',
            pdesc:'',
            address:'',
        });
        let SettingFormvisit = reactive({
            groups:[],
            uids:[],
            data:[],
            checked:[],
            expanded:[],
            status:0
        });
        let SettingFormdown = reactive({
            groups:[],
            uids:[],
            data:[],
            checked:[],
            expanded:[],
            status:0
        });
        let SettingFormshare = reactive({
            groups:[],
            uids:[],
            data:[],
            checked:[],
            expanded:[],
            status:0
        });
        return {
            DataList,
            SettingFormvisit,
            SettingFormdown,
            SettingFormshare
        }
    }
};