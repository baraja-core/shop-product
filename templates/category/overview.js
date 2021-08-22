Vue.component('cms-product-category-overview', {
	props: ['id'],
	template: `<b-card>
	<div v-if="category === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<template v-else>
		<b-form @submit="save">
			<div class="container-fluid">
				<div class="row">
					<div class="col">
						Name:
						<b-form-input v-model="category.name"></b-form-input>
					</div>
					<div class="col">
						Code:
						<b-form-input v-model="category.code" disabled></b-form-input>
					</div>
					<div class="col">
						Parent:
						<b-form-select v-model="category.parentId" :options="tree"></b-form-select>
					</div>
				</div>
				<div class="row mt-3">
					<div class="col">
						Description:
						<b-form-textarea v-model="category.description" rows="5"></b-form-textarea>
					</div>
				</div>
				<div class="row mt-3">
					<div class="col">
						<b-button type="submit" variant="primary" @click="save">Save</b-button>
					</div>
				</div>
			</div>
		</b-form>
	</template>
</b-card>`,
	data() {
		return {
			category: null,
			tree: null
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product-category/overview?id=${this.id}`)
				.then(req => {
					this.category = req.data.category;
					this.tree = req.data.tree;
				});
		},
		save(evt) {
			evt.preventDefault();
			axiosApi.post('cms-product-category/save', {
				id: this.id,
				name: this.category.name,
				parentId: this.category.parentId,
				description: this.category.description
			}).then(req => {
				this.sync();
			});
		}
	}
});
