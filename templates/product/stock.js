Vue.component('cms-product-stock', {
	props: ['id'],
	template: `<cms-card>
	<div v-if="size === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div v-else class="container-fluid">
		<div class="row mb-4">
			<div class="col">
				Weight:
				<b-input-group append="g">
					<b-form-input v-model="weight"></b-form-input>
				</b-input-group>
			</div>
			<div class="col">
				Size (width X length X thickness):
				<div class="row">
					<div class="col">
						<b-form-input v-model="size.width" class="form-control-primary"></b-form-input>
					</div>
					<div class="col">
						<b-form-input v-model="size.length"></b-form-input>
					</div>
					<div class="col">
						<b-form-input v-model="size.thickness"></b-form-input>
					</div>
				</div>
			</div>
		</div>
		<b-button variant="primary" @click="save">Save</b-button>
	</div>
</cms-card>`,
	data() {
		return {
			weight: null,
			size: null
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/stock?id=${this.id}`)
				.then(req => {
					this.weight = req.data.weight;
					this.size = req.data.size;
				});
		},
		save() {
			axiosApi.post('cms-product/stock', {
				id: this.id,
				weight: this.weight,
				width: this.size.width,
				length: this.size.length,
				thickness: this.size.thickness,
			}).then(req => {
				this.syncCustomFields(true);
			});
		}
	}
});
