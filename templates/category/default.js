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
	<div v-if="items === null" class="text-center py-5">
		<b-spinner></b-spinner>
	</div>
	<b-card v-else>
		<div v-if="items.length === 0" class="text-center my-5">
			Category list is empty.
			<div class="mt-5">
				<b-button variant="primary" v-b-modal.modal-new-category>Create first category</b-button>
			</div>
		</div>
		<template v-else>
			<table class="table table-sm">
				<tr>
					<th>Název</th>
					<th>Rodič</th>
					<th width="150">Kód</th>
					<th v-if="heurekaAvailable" width="100">Heureka ID</th>
				</tr>
				<tr v-for="item in items">
					<td>
						<a :href="link('ProductCategory:detail', { id: item.id })">{{ item.name }}</a>
					</td>
					<td>{{ item.parent ? item.parent.name : '' }}</td>
					<td><code>{{ item.code }}</code></td>
					<td v-if="heurekaAvailable">{{ item.heurekaCategoryId }}</td>
				</tr>
			</table>
		</template>
	</b-card>
	<b-modal id="modal-new-category" title="New category" hide-footer>
		<b-form @submit="createNewCategory">
			Name:
			<input v-model="newCategory.name" class="form-control">
			<b-button type="submit" variant="primary" class="mt-3">Create</b-button>
		</b-form>
	</b-modal>
</div>`,
	data() {
		return {
			items: null,
			heurekaAvailable: false,
			newCategory: {
				name: ''
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
					this.items = req.data.items;
					this.heurekaAvailable = req.data.heurekaAvailable;
				});
		},
		createNewCategory(evt) {
			evt.preventDefault();
			if (!this.newCategory.name) {
				alert('Vyplňte, prosím, všechna pole.');
				return;
			}
			axiosApi.post('cms-product-category/create-category', {
				name: this.newCategory.name
			}).then(req => {
				this.sync();
			});
		}
	}
});
