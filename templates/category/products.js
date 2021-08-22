Vue.component('cms-product-category-products', {
	props: ['id'],
	template: `<b-card>
	<div v-if="products === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<template v-else>
		<div v-if="products.length === 0" class="text-center text-secondary my-5">
			Here are not products.
		</div>
		<table v-else class="table table-sm cms-table-no-border-top">
			<tr>
				<th>Name</th>
				<th>Price</th>
				<th>Active</th>
			</tr>
			<tr v-for="product in products">
				<td><a :href="link('Product:detail', {id: product.id})">{{ product.name }}</a></td>
				<td>{{ product.price }}</td>
				<td>{{ product.active ? 'yes' : 'no' }}</td>
			</tr>
		</table>
	</template>
</b-card>`,
	data() {
		return {
			products: null
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product-category/products?id=${this.id}`)
				.then(req => {
					this.products = req.data.products;
				});
		}
	}
});
