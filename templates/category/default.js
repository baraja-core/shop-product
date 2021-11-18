Vue.component('cms-product-category-default', {
	template: `<div class="container-fluid">
	<div class="row mt-2">
		<div class="col">
			<h1>Product categories</h1>
		</div>
		<div class="col-3 text-right">
			<b-button variant="primary" v-b-modal.modal-new-category>New category</b-button>
		</div>
	</div>
	<div v-if="dataExist === null" class="text-center py-5">
		<b-spinner></b-spinner>
	</div>
	<b-card v-else>
		<div v-if="dataExist === false" class="text-center my-5">
			Category list is empty.
			<div class="mt-5">
				<b-button variant="primary" v-b-modal.modal-new-category>Create first category</b-button>
			</div>
		</div>
		<template v-else>
			<cms-product-category-tree :parent-id="null"></cms-product-category-tree>
		</template>
	</b-card>
	<b-modal id="modal-new-category" title="New category" hide-footer>
		<b-form @submit="createNewCategory">
			<label>Name:</label>
			<input v-model="newCategory.name" class="form-control">
			<label class="mt-3">Parent:</label>
			<b-form-select v-model="newCategory.parentId" :options="tree"></b-form-select>
			<b-button type="submit" variant="primary" class="mt-3">Create</b-button>
		</b-form>
	</b-modal>
</div>`,
	data() {
		return {
			dataExist: null,
			tree: null,
			newCategory: {
				name: '',
				parentId: null
			}
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync: function () {
			axiosApi.get(`cms-product-category`)
				.then(req => {
					this.dataExist = req.data.dataExist;
					this.tree = req.data.tree;
				});
		},
		createNewCategory(evt) {
			evt.preventDefault();
			if (!this.newCategory.name) {
				alert('Vyplňte, prosím, všechna pole.');
				return;
			}
			axiosApi.post('cms-product-category/create-category', {
				name: this.newCategory.name,
				parentId: this.newCategory.parentId,
			}).then(req => {
				this.sync();
			});
		}
	}
});

Vue.component('cms-product-category-tree', {
	props: ['parentId'],
	template: `<div :style="parentId !== null ? 'padding-left:32px' : ''">
	<div v-if="items === null" class="my-3">
		<b-spinner small></b-spinner>
	</div>
	<table v-else class="table table-sm m-0 cms-table-no-border-top">
		<tr v-if="parentId === null">
			<th></th>
			<th>Name</th>
			<th>Code</th>
		</tr>
		<template v-for="(item, key) in items">
			<tr>
				<td width="20" class="px-0" :style="parentId !== null && key === 0 ? 'border-top:0' : ''">
					<span v-if="item.hasChildren" style="cursor:pointer">
						<b-icon-chevron-down v-if="openChildren[item.id]" @click="openChildren[item.id]=false"></b-icon-chevron-down>
						<b-icon-chevron-right v-else @click="openChildren[item.id]=true"></b-icon-chevron-right>
					</span>
				</td>
				<td :style="parentId !== null && key === 0 ? 'border-top:0' : ''">
					<a :href="link('ProductCategory:detail', { id: item.id })">
						<template v-if="item.active">{{ item.name }}</template>
						<template v-else><s>{{ item.name }}</s></template>
					</a>
					<small v-if="item.active === false" class="text-secondary">(hidden)</small>
				</td>
				<td width="230" :style="parentId !== null && key === 0 ? 'border-top:0' : ''"><code>{{ item.code }}</code></td>
			</tr>
			<tr v-if="openChildren[item.id]">
				<td colspan="5" class="p-0">
					<cms-product-category-tree :parentId="item.id"></cms-product-category-tree>
				</td>
			</tr>
		</template>
	</table>
</div>`,
	data() {
		return {
			items: null,
			openChildren: []
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product-category/default-tree?parentId=` + this.parentId)
				.then(req => {
					this.items = req.data.items;
					this.openChildren = req.data.openChildren;
				});
		}
	}
});
