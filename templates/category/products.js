Vue.component('cms-product-category-products', {
	props: ['id'],
	template: `<b-card>
	<div v-if="products === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div v-else class="container-fluid">
		Work in progress.
	</div>
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
					this.products = req.products;
				});
		}
	}
});
