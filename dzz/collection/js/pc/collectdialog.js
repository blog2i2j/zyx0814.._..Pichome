
Vue.component('star-tree-dialog', {
	props:['lids','dialogvisible','operationtype','clid','cid','uid'],
	template: `<el-dialog
					:title="lang.text1"
					custom-class="collection-dialog"
					@open="PopoverShow"
					@close="PopoverHide"
					:visible.sync="dialogvisible">
					<div class="collection-dialog-content">
						<div class="header">
							<el-input
								clearable 
								v-model.trim="filterText" 
								:placeholder="lang.text2"><i slot="prefix" class="el-input__icon ri-search-line"></i></el-input>
						</div>
						<div class="content">
							<el-scrollbar class="h350">
								<div style="padding:0 12px;">
									<el-tree
										v-if="NodeShow"
										ref="lefttree"
										:load="GetTreeData"
										node-key="cid"
										lazy
										:props="defaultProps"
										:filter-node-method="TreeDataFilterNode"
										:default-expanded-keys="ExpandedNodeKeys">
											<div class="custom-tree-node" :class="{notpadding:!data.parent}" slot-scope="{ node, data }">
												
												<div class="name" v-cloak v-html="data.pcatname"></div>
												<ul class="avatar">
													<li v-for="item in data.uids" v-html="item.icon"></li>
												</ul>
												<el-button class="btn" @click.stop.prevent="handleSubmit(data.cid,data.clid,data.parent)" type="primary" size="medium">
													<template v-if="operationtype=='move'">{{lang.text4}}</template>
													<template v-else>{{lang.text3}}</template>
												</el-button>
											</div>
										</el-tree>
								</div>
							</el-scrollbar>
						</div>
					</div>
				</el-dialog>`,
	data: function() {
		return {
			filterText:'',
			defaultProps: {
				children: 'children',
				label: 'catname'
			},
			loading:true,
			NodeShow:false,
			ExpandedNodeKeys:[],
			alreadyExpandedNodeKeys:[],
			lang:{
				text1:__lang.select_favorites,
				text2:__lang.search_favorites,
				text3:__lang.collect,
				text4:__lang.move
			}
		}
	},
	watch:{
		filterText:debounce(async function(val){
			var self = this;
			if(val){
				self.alreadyExpandedNodeKeys = [];
				self.ExpandedNodeKeys = [];
				self.loading = true;
				this.NodeShow = false;
				pvue.LoginFunc = 'refresh';
				pvue.LoginParam = null;
				var res = await axios.post(SITEURL+DZZSCRIPT+'?mod=collection&op=collect&do=searchcollect',{keyword:val})
				if(res == 'intercept'){
					return false;
				}
				var json = res.data;
				var data = [];
				for(var i in json.clid){
					var id = json.clid[i];
					if(data.indexOf('p'+id)<0){
						data.push('p'+id);
					}
				}
				for(var x in json.cids){
					var id = json.cids[x];
					if(data.indexOf(parseInt(id))<0){
						data.push(parseInt(id));
					}
				}
				self.ExpandedNodeKeys = data;
				self.$nextTick(function(){
					self.NodeShow = true;
				});
			}else{
				self.filterTextclear();
			}
		},800)
	},
	created() {
	},
	methods:{
		filterTextclear(){
			var self = this;
			self.alreadyExpandedNodeKeys = [];
			self.ExpandedNodeKeys = [];
			self.loading = true;
			self.NodeShow = false;
			self.$nextTick(function(){
				self.NodeShow = true;
			});
		},
		async GetTreeData(node,resolve){
			var self = this;
			var param = {};
			if(node.level == 1){
				param = {
					clid:node.data.cid.replace('p','')
				}
			}
			if(node.level > 1){
				param = {
					cid:node.data.cid,
					clid:node.data.clid
				}
			}
			pvue.LoginFunc = 'refresh';
			pvue.LoginParam = null;
			var res = await axios.post(SITEURL+DZZSCRIPT+'?mod=collection&op=collect&do=collectlist',param);
			if(res == 'intercept'){
				return false;
			}
			var json = res.data;
			var data = [];
			for(var i in json.success){
				var item = json.success[i];
				if(node.level == 0){
					item['cid'] = 'p'+item.clid;
					item['catname'] = item.name;
					item['parent'] = true;
					self.alreadyExpandedNodeKeys.push(item['cid']);
				}else{
					item['cid'] = parseInt(item.cid);
					item['parent'] = false;
					self.alreadyExpandedNodeKeys.push(parseInt(item['cid']));
				}
				if(self.filterText){
					item['pcatname'] = self.handleHighlight(item['catname'],self.filterText);
				}else{
					item['pcatname'] = item['catname'];
				}
				data.push(item)
			}
			resolve(data);
			self.$nextTick(function(){
				self.GetTreeDataFinish();
			});
		},
		GetTreeDataFinish(){
			var self = this;
			var finish = false;
			if(self.ExpandedNodeKeys.length){
				for(var i in self.ExpandedNodeKeys){
					var id = self.ExpandedNodeKeys[i];
					if(self.alreadyExpandedNodeKeys.indexOf(id)>-1){
						finish = true;
					}else{
						return false;
					}
				}
				if(finish){
					if(self.filterText){
						self.$refs['lefttree'].filter(self.filterText);
					}
					self.loading = false;
				}
			}else{
				if(self.filterText){
					self.$refs['lefttree'].filter(self.filterText);
				}
				self.loading = false;
			}
		},
		async handleSubmit(cid,clid,parent){
			var param = {}
			var self = this;
			if(self.operationtype == 'collect'){
				if(parent){
					param = {
						lids:this.lids.join(','),
						clid:cid.replace('p','')
					}
				}else{
					param = {
						lids:this.lids.join(','),
						cid:cid,
						clid:clid,
					}
				}
				pvue.LoginFunc = 'refresh';
				pvue.LoginParam = null;
				var res = await axios.post(MOD_URL+'&op=collect&do=collectfiletocollect',param);
				if(res == 'intercept'){
					return false;
				}
				var json = res.data;
				if(json.success){
					var node = self.$refs['lefttree'].getNode(cid);
					if(parent){
						var collect = {
							name:node.data.catname,
							key:cid
						};
					}else{
						// var collectkey = clid+'-'+node.data.pathkey.replace('_','');
						var collect = {
							name:node.data.catname,
							key:'p'+clid+'-'+node.data.pathkey.replaceAll('_','')
						};
					}
					sessionStorage.setItem(self.uid+'_collectkey', JSON.stringify(collect));
					self.$message({
						type:'success',
						message:__lang.file_collection_successful
					});
					self.$emit('addcollectsuccess');
					self.PopoverHide();
				}else{
					self.$message.error(json.error);
				}
			}else{
				if(parent){
					param = {
						lids:this.lids.join(','),
						clid:cid.replace('p',''),
						// oclid:self.clid,
						// ocid:self.cid!='all'&&self.cid!='not'?self.cid:'',
					}
				}else{
					param = {
						lids:this.lids.join(','),
						cid:cid,
						clid:clid,
						// oclid:self.clid,
						// ocid:self.cid!='all'&&self.cid!='not'?self.cid:'',
					}
				}
				pvue.LoginFunc = 'refresh';
				pvue.LoginParam = null;
				var res = await axios.post(MOD_URL+'&op=collect&do=movefiletocollect',param);
				if(res == 'intercept'){
					return false;
				}
				var json = res.data;
				if(json.success){
					var node = self.$refs['lefttree'].getNode(cid);
					if(parent){
						var collect = {
							name:node.data.catname,
							key:cid
						};
					}else{
						// var collectkey = clid+'-'+node.data.pathkey;
						var collect = {
							name:node.data.catname,
							key:'p'+clid+'-'+node.data.pathkey.replaceAll('_','')
						};
					}
					sessionStorage.setItem(self.uid+'_collectkey', JSON.stringify(collect));
					self.$message({
						type:'success',
						message:__lang.move_success
					});
					self.$emit('addcollectsuccess',self.operationtype);
					self.PopoverHide();
				}else{
					self.$message.error(json.error);
				}
			}
			
		},
		PopoverShow(){
			var self = this;
			var collectkey = JSON.parse(sessionStorage.getItem(self.uid+'_collectkey'));
			if(collectkey){
				var keys = collectkey.key.split('-');
				if(keys.length>1){
					keys.pop();
				}
				var newkeys = [];
				for(var i in keys){
					if(keys[i].indexOf('p')>-1){
						newkeys.push(keys[i]);
					}else{
						newkeys.push(parseInt(keys[i]));
					}
				}
				this.ExpandedNodeKeys = newkeys;
			}
			
			this.NodeShow = true;
		},
		PopoverHide(){
			var self = this;
			self.NodeShow = false;
			self.filterText = '';
			self.alreadyExpandedNodeKeys = [];
			self.$emit('closecollectdialog');
		},
		handleOpenAdd(){
			var self = this;
			self.$emit('openaddcollect',this.lids);
		},
		TreeDataFilterNode(value, data) {
			if (!value) return true;
			return data.catname.indexOf(value) !== -1;
		},
		handleHighlight(text,words){
			// 默认的标签，如果没有指定，使用span
			var i, len = words.length,
			re;
			//匹配每一个特殊字符 ，进行转义
			var specialStr = ["*", ".", "?", "+", "$", "^", "[", "]", "{", "}", "|", "\\", "(", ")", "/", "%"]; 
			$.each(specialStr, function(i, item) {
				if(words.indexOf(item) != -1) {
					words = words.replace(new RegExp("\\" + item, 'g'), "\\" + item);
				}
			});
			//匹配整个关键词
			re = new RegExp(words, 'g');
			if(re.test(text)) {
				text = text.replace(re, '<span class="highlight">$&</span>');
			}
			return text;
		},
	},
	mounted() {
		
	},
	beforeRouteLeave() {
	},
});
