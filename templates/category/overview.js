Vue.component('cms-product-category-overview', {
	props: ['id'],
	template: `<cms-card>
	<div v-if="category === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<template v-else>
		<b-form @submit="save">
			<div class="row">
				<div class="col">
					Name:
					<b-form-input v-model="category.name"></b-form-input>
				</div>
				<div class="col">
					<div class="container-fluid">
						<div class="row">
							<div class="col px-0">
								Code:
							</div>
							<div class="col px-0 text-right">
								<span v-if="options.code" class="text-secondary" @click="options.code=false">(edit)</span>
							</div>
						</div>
					</div>
					<b-form-input v-model="category.code" :disabled="options.code"></b-form-input>
				</div>
				<div class="col">
					<div class="container-fluid">
						<div class="row">
							<div class="col px-0">
								Slug:
							</div>
							<div class="col px-0 text-right">
								<span v-if="options.slug" class="text-secondary" @click="options.slug=false">(edit)</span>
							</div>
						</div>
					</div>
					<b-form-input v-model="category.slug" :disabled="options.slug"></b-form-input>
				</div>
			</div>
			<div class="row">
				<div class="col">
					Parent:
					<b-form-select v-model="category.parentId" :options="tree"></b-form-select>
				</div>
				<div class="col pt-2">
					<label>
						Active?<br>
						<b-form-checkbox v-model="category.active"></b-form-checkbox>
					</label>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col">
					<cms-editor v-model="category.description" label="Description:" rows="5"></cms-editor>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col">
					<b-button type="submit" variant="primary" @click="save">Save</b-button>
				</div>
			</div>
		</b-form>
	</template>
</cms-card>`,
	data() {
		return {
			category: null,
			tree: null,
			options: {
				code: true,
				slug: true
			}
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
				code: this.category.code,
				slug: this.category.slug,
				parentId: this.category.parentId,
				description: this.category.description,
				active: this.category.active
			}).then(() => {
				this.sync();
			});
		}
	}
});
