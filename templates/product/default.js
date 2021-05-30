Vue.component('cms-product-default', {
	template: `<div class="container-fluid">
	<div class="row mt-2">
		<div class="col">
			<h1>Products</h1>
		</div>
		<div class="col-3 text-right">
			<b-button variant="primary" v-b-modal.modal-new-product>New product</b-button>
		</div>
	</div>
	<div v-if="items === null" class="text-center py-5">
		<b-spinner></b-spinner>
	</div>
	<template v-else>
		<cms-filter>
			<b-form inline class="w-100">
				<div class="w-100">
					<div class="d-flex flex-column flex-sm-row align-items-sm-center pr-lg-0">
						<div class="row w-100">
							<div class="col">
								<b-form-input size="sm" v-model="filter.query" @input="sync" class="mr-3 w-100" style="max-width:300px" placeholder="Search anywhere..."></b-form-input>
							</div>
							<div class="col-1 text-right">
								<b-form-select size="sm" v-model="filter.limit" :options="limitOptions" @change="sync"></b-form-select>
							</div>
						</div>
					</div>
				</div>
			</b-form>
		</cms-filter>
		<b-card>
			<div v-if="count === 0" class="text-center my-5">
				Product list is empty.
				<div class="mt-5">
					<b-button variant="primary" v-b-modal.modal-new-product>Create first product</b-button>
				</div>
			</div>
			<template v-else>
				<div class="row">
					<div class="col">
						<b>{{ count }}</b> products shown
					</div>
					<div class="col-4">
						<b-pagination
							v-model="paginator.page"
							:per-page="paginator.itemsPerPage"
							@change="syncPaginator()"
							:total-rows="paginator.itemCount" align="right" size="sm">
						</b-pagination>
					</div>
				</div>
				<table class="table table-sm">
					<tr>
						<th width="100">Image</th>
						<th>Name</th>
						<th width="150">Category</th>
						<th width="80">Position</th>
						<th width="100">Price</th>
					</tr>
					<tr v-for="item in items">
						<td>
							<a :href="link('Product:detail', { id: item.id })">
								<template v-if="item.mainImage">
									<img :src="basePath + '/product-image/' + item.mainImage.source">
								</template>
								<template v-else>
									<img src="https://cdn.baraja.cz/icon/no-product-image.jpg" class="w-100" alt="No product image">
								</template>
							</a>
						</td>
						<td>
							<div>
								<a :href="link('Product:detail', { id: item.id })">{{ item.name }}</a>
								<span @click="makeActive(item.id)" style="cursor:pointer">
									<span v-if="item.active" class="badge badge-success">Active</span>
									<span v-else class="badge badge-danger">Hidden </span>
								</span>
								<span v-if="item.soldOut" class="badge badge-warning">Sold&nbsp;out</span>
							</div>
							<div>
								Code: <code>{{ item.code }}</code> | EAN: <code>{{ item.ean }}</code>
							</div>
							<p class="text-secondary">
								{{ item.shortDescription }}
							</p>
						</td>
						<td>
							<template v-if="item.mainCategory">
								{{ item.mainCategory.name }}
							</template>
						</td>
						<td>
							<b-form-input type="number" v-model="item.position" min="0" max="1000" size="sm" @change="changePosition(item.id, item.position)"></b-form-input>
						</td>
						<td>{{ item.price }}&nbsp;Kč</td>
					</tr>
				</table>
				<div class="text-right">
					<b-pagination
						v-model="paginator.page"
						:per-page="paginator.itemsPerPage"
						@change="syncPaginator()"
						:total-rows="paginator.itemCount" align="right" size="sm">
					</b-pagination>
				</div>
			</template>
		</b-card>
	</template>
	<b-modal id="modal-new-product" :title="'Create ' + (count === 0 ? 'first' : 'new') + ' product'" hide-footer>
		<b-form @submit="createNewProduct">
			<div class="mb-3">
				Name:
				<input v-model="newProduct.name" class="form-control">
			</div>
			<div class="mb-3">
				Code:
				<input v-model="newProduct.code" class="form-control">
			</div>
			<div class="mb-3">
				Primary price:
				<input v-model="newProduct.price" class="form-control">
			</div>
			<b-button type="submit" variant="primary" class="mt-3">Create new product</b-button>
		</b-form>
	</b-modal>
</div>`,
	data() {
		return {
			items: null,
			count: null,
			paginator: {
				itemsPerPage: 0,
				page: 1,
				itemCount: 0,
			},
			newProduct: {
				name: '',
				code: '',
				price: 0
			},
			filter: {
				query: '',
				limit: 32
			},
			limitOptions: [
				{value: 32, text: '32'},
				{value: 64, text: '64'},
				{value: 128, text: '128'},
				{value: 256, text: '256'},
				{value: 512, text: '512'},
				{value: 1024, text: '1024'},
				{value: 2048, text: '2048'}
			]
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			let query = {
				query: this.filter.query ? this.filter.query : null,
				page: this.paginator.page,
				limit: this.filter.limit
			};
			axiosApi.get('cms-product?' + httpBuildQuery(query))
				.then(req => {
					this.count = req.data.count;
					this.items = req.data.items;
					this.paginator = req.data.paginator;
				});
		},
		syncPaginator() {
			setTimeout(this.sync, 50);
		},
		createNewProduct(evt) {
			evt.preventDefault();
			if (!this.newProduct.name || !this.newProduct.code || !this.newProduct.price) {
				alert('Vyplňte, prosím, všechna pole.');
				return;
			}
			axiosApi.post('cms-product/create-product', {
				name: this.newProduct.name,
				code: this.newProduct.code,
				price: this.newProduct.price
			}).then(req => {
				window.location.href = link('Product:detail', {
					id: req.data.id
				});
				this.sync();
			});
		},
		makeActive(id) {
			if (!confirm('Really?')) {
				return;
			}
			axiosApi.post('cms-product/active', {
				id: id
			}).then(req => {
				this.sync();
			});
		},
		changePosition(id, position) {
			axiosApi.post('cms-product/set-position', {
				id: id,
				position: position
			}).then(req => {
				this.sync();
			});
		}
	}
});
