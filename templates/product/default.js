Vue.component('cms-product-default', {
	template: `<div class="container-fluid">
	<div class="row mt-2">
		<div class="col">
			<h1>Products</h1>
		</div>
		<div class="col-3 text-right">
			<b-button variant="secondary" v-b-modal.modal-field-manager>Field manager</b-button>
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
				<table class="table table-sm cms-table-no-border-top">
					<tr>
						<th width="100" class="pl-0">Image</th>
						<th>Name</th>
						<th width="150">Category</th>
						<th width="80">Position</th>
						<th width="100">Price</th>
					</tr>
					<tr v-for="item in items">
						<td class="py-0 pl-0">
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
								<span @click="makeActive(item.id)" style="cursor:pointer">
									<span v-if="item.active" v-b-tooltip title="Product is active.">🟢</span>
									<span v-else v-b-tooltip title="Product is hidden.">🔴</span>
								</span>
								<span v-if="item.soldOut" v-b-tooltip title="Product has been sold out.">💰</span>
								<a :href="link('Product:detail', { id: item.id })">{{ item.name }}</a>
							</div>
							<div>
								Code: <code>{{ item.code }}</code>
								<template v-if="item.ean !== null">| EAN: {{ item.ean }}</template>
								<template v-if="item.brand !== null">| Brand: {{ item.brand.name }}</template>
							</div>
							<p class="text-secondary">
								{{ item.shortDescription }}
							</p>
						</td>
						<td>
							<template v-if="item.mainCategory">
								{{ item.mainCategory.name }}
							</template>
							<template v-else>
								<span class="text-secondary">Please select.</span>
							</template>
						</td>
						<td>
							<b-form-input type="number" v-model="item.position" min="0" max="1000" size="sm" @change="changePosition(item.id, item.position)"></b-form-input>
						</td>
						<td v-html="item.priceRender"></td>
					</tr>
				</table>
				<div class="text-center">
					<b-pagination
						v-model="paginator.page"
						:per-page="paginator.itemsPerPage"
						@change="syncPaginator()"
						:total-rows="paginator.itemCount" align="center" size="sm">
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
	<b-modal id="modal-field-manager" title="Product field manager" size="xl" @shown="syncCustomFields" hide-footer>
		<div v-if="customField.list === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<div class="mb-3 text-right">
				<b-button variant="primary" size="sm" v-b-modal.modal-new-field-definition>Add field</b-button>
			</div>
			<div v-if="customField.list.length === 0" class="text-center my-5 text-secondary">
				Field definition list is empty.
			</div>
			<table v-else class="table table-sm">
				<tr>
					<th>Name / Label</th>
					<th width="160">Value type</th>
					<th>Description</th>
					<th width="80" class="text-center">Required</th>
					<th width="80" class="text-center">Length</th>
					<th width="80" class="text-center">Unique</th>
					<th width="80" class="text-center">Position</th>
				</tr>
				<tr v-for="fieldDefinition in customField.list">
					<td>
						<table class="w-100" cellspacing="0" cellpadding="0">
							<tr>
								<th class="p-0" style="border:0">Name:</th>
								<td class="py-0" style="border:0">
									<input v-model="fieldDefinition.name" class="form-control form-control-sm">
								</td>
							</tr>
							<tr>
								<th class="p-0" style="border:0">Label:</th>
								<td class="py-0" style="border:0">
									<input v-model="fieldDefinition.label" class="form-control form-control-sm">
								</td>
							</tr>
						</table>
					</td>
					<td>
						<b-form-select size="sm" v-model="fieldDefinition.type" :options="customField.types"></b-form-select>
					</td>
					<td class="text-secondary">
						<textarea v-model="fieldDefinition.description" class="form-control form-control-sm" rows="2"></textarea>
					</td>
					<td class="text-center">
						<button :class="['btn', 'btn-sm', 'py-0', fieldDefinition.required ? 'btn-success' : 'btn-danger']" @click="fieldDefinition.required=!fieldDefinition.required">
							{{ fieldDefinition.required ? 'YES' : 'NO' }}
						</button>
					</td>
					<td><input type="number" v-model="fieldDefinition.length" class="form-control form-control-sm"></td>
					<td class="text-center">
						<button :class="['btn', 'btn-sm', 'py-0', fieldDefinition.unique ? 'btn-success' : 'btn-danger']" @click="fieldDefinition.unique=!fieldDefinition.unique">
							{{ fieldDefinition.unique ? 'YES' : 'NO' }}
						</button>
					</td>
					<td><input type="number" v-model="fieldDefinition.position" class="form-control form-control-sm"></td>
				</tr>
			</table>
			<b-button type="submit" variant="primary" class="mt-3" @click="saveCustomFields">Save</b-button>
		</template>
	</b-modal>
	<b-modal id="modal-new-field-definition" title="Create new field definition" hide-footer>
		<div class="mb-3">
			Name <span class="text-secondary">(must be unique)</span>:
			<input v-model="customField.newDefinition.name" class="form-control">
		</div>
		<div class="mb-3">
			Type:
			<b-form-select v-model="customField.newDefinition.type" :options="customField.types"></b-form-select>
		</div>
		<b-button type="submit" variant="primary" class="mt-3" @click="addDefinition">Create</b-button>
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
				price: 0,
			},
			filter: {
				query: '',
				limit: 32,
			},
			limitOptions: [
				{value: 32, text: '32'},
				{value: 64, text: '64'},
				{value: 128, text: '128'},
				{value: 256, text: '256'},
				{value: 512, text: '512'},
				{value: 1024, text: '1024'},
				{value: 2048, text: '2048'},
			],
			customField: {
				list: null,
				newDefinition: {
					name: '',
					type: 'string',
				},
				types: [
					{value: 'string', text: 'Single line text'},
					{value: 'text', text: 'Multi line text'},
					{value: 'int', text: 'Number (int)'},
				],
			},
		};
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			let query = {
				query: this.filter.query ? this.filter.query : null,
				page: this.paginator.page,
				limit: this.filter.limit,
			};
			axiosApi.get('cms-product?' + httpBuildQuery(query)).then((req) => {
				this.count = req.data.count;
				this.items = req.data.items;
				this.paginator = req.data.paginator;
			});
		},
		syncCustomFields(flush = false) {
			if (this.customField.list !== null && flush === false) {
				return;
			}
			this.customField.list = null;
			axiosApi.get('cms-product-field').then((req) => {
				this.customField.list = req.data.items;
			});
		},
		syncPaginator() {
			setTimeout(this.sync, 50);
		},
		createNewProduct(evt) {
			evt.preventDefault();
			if (
				!this.newProduct.name ||
				!this.newProduct.code ||
				!this.newProduct.price
			) {
				alert('Vyplňte, prosím, všechna pole.');
				return;
			}
			axiosApi
				.post('cms-product/create-product', {
					name: this.newProduct.name,
					code: this.newProduct.code,
					price: this.newProduct.price,
				})
				.then((req) => {
					window.location.href = link('Product:detail', {
						id: req.data.id,
					});
					this.sync();
				});
		},
		makeActive(id) {
			if (!confirm('Really?')) {
				return;
			}
			axiosApi
				.post('cms-product/active', {
					id: id,
				})
				.then(() => {
					this.sync();
				});
		},
		changePosition(id, position) {
			axiosApi
				.post('cms-product/set-position', {
					id: id,
					position: position,
				})
				.then(() => {
					this.sync();
				});
		},
		addDefinition() {
			axiosApi
				.post('cms-product-field/add-definition', {
					name: this.customField.newDefinition.name,
					type: this.customField.newDefinition.type,
				})
				.then(() => {
					this.syncCustomFields(true);
				});
		},
		saveCustomFields() {
			axiosApi
				.post('cms-product-field/save', {
					fields: this.customField.list,
				})
				.then(() => {
					this.syncCustomFields(true);
				});
		},
	},
});
