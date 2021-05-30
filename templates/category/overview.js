Vue.component('cms-product-category-overview', {
	props: ['id'],
	template: `<b-card>
	<div v-if="category === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div v-else class="container-fluid">
		Work in progress.
	</div>
</b-card>`,
	data() {
		return {
			category: null
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product-category/overview?id=${this.id}`)
				.then(req => {
					this.category = req.data;
				});
		}
	}
});
