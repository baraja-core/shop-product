Vue.component('cms-product-related', {
	props: ['id'],
	template: `<cms-card>
		<div class="text-right mb-3">
			<b-button variant="primary" size="sm" v-b-modal.modal-add-related>Add related</b-button>
		</div>
		<div v-if="list === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<div v-if="list.length === 0" class="text-center my-5">
				There are no related products.
			</div>
			<table v-else class="table table-sm cms-table-no-border-top">
				<tr>
					<th>Product</th>
					<th>Main category</th>
					<th></th>
				</tr>
				<tr v-for="product in list">
					<td>
						<a :href="link('Product:detail', { id: product.id })" target="_blank">{{ product.name }}</a>
					</td>
					<td>
						<template v-if="product.mainCategory">
							<a :href="link('ProductCategory:detail', { id: product.mainCategory.id })" target="_blank">
								{{ product.mainCategory.name }}
							</a>
						</template>
					</td>
					<td class="text-right">
						<b-button variant="danger" size="sm" class="px-2 py-0" @click="deleteRelatedProduct(product.id)">remove</b-button>
					</td>
				</tr>
			</table>
		</template>
		<b-modal id="modal-add-related" title="Add related product" size="lg" @shown="loadCandidates" hide-footer>
			<div v-if="candidates === null" class="my-5 text-center">
				<b-spinner></b-spinner>
			</div>
			<template v-else>
				<div class="row mb-3">
					<div class="col">
						<b-form-input v-model="query" placeholder="Search products..."></b-form-input>
					</div>
					<div class="col-2 text-right">
						<b-button type="submit" variant="primary" @click="loadCandidates">Search</b-button>
					</div>
				</div>
				<div v-if="candidates.length === 0" class="text-center my-5">
					There are not results.
				</div>
				<table v-else class="table table-sm">
					<tr>
						<th>Product</th>
						<th>Main category</th>
						<th></th>
					</tr>
					<tr v-for="candidate in candidates">
						<td>
							<a :href="link('Product:detail', { id: candidate.id })" target="_blank">{{ candidate.name }}</a>
						</td>
						<td>
							<template v-if="candidate.mainCategory">
								<a :href="link('ProductCategory:detail', { id: candidate.mainCategory.id })" target="_blank">
									{{ candidate.mainCategory.name }}
								</a>
							</template>
						</td>
						<td class="text-right">
							<b-button variant="primary" size="sm" class="px-2 py-0" @click="addRelatedProduct(candidate.id)">add</b-button>
						</td>
					</tr>
				</table>
			</template>
		</b-modal>
	</cms-card>`,
	data() {
		return {
			list: null,
			candidates: null,
			query: ''
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/related?id=${this.id}`)
				.then(req => {
					this.list = req.data.items;
				});
		},
		loadCandidates() {
			axiosApi.get(`cms-product/related-candidates?id=${this.id}&query=${this.query}`)
				.then(req => {
					this.candidates = req.data.items;
				});
		},
		addRelatedProduct(id) {
			axiosApi.get(`cms-product/add-related?id=${this.id}&relatedId=${id}`)
				.then(req => {
					this.loadCandidates();
					this.sync();
				});
		},
		deleteRelatedProduct(id) {
			axiosApi.get(`cms-product/delete-related?id=${this.id}&relatedId=${id}`)
				.then(req => {
					this.sync();
				});
		}
	}
});
