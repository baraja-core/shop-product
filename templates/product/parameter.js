Vue.component('cms-product-parameter', {
	props: ['id'],
	template: `<b-card>
		<div v-if="parameters === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<h4>Parameters</h4>
			<template v-if="parameters.length > 0">
				<b-form @submit="saveChanges">
					<table class="table table-sm">
						<tr>
							<th>Parameter</th>
							<th>Values</th>
							<th>Is variant?</th>
							<th></th>
						</tr>
						<tr v-for="parameter in parameters">
							<td><input v-model="parameter.name" class="form-control"></td>
							<td><b-form-tags v-model="parameter.values" separator=",;"></b-form-tags></td>
							<td><input type="checkbox" v-model="parameter.variant"></td>
							<td>
								<b-button variant="danger" size="sm" class="px-1 py-0" @click="deleteParameter(parameter.id)">remove</b-button>
							</td>
						</tr>
					</table>
					<b-button type="submit" variant="primary" class="mt-3">Save changes</b-button>
				</b-form>
			</template>
			<div v-else class="text-center my-5">
				<p>The product has no parameters.</p>
			</div>
			<h4 class="mt-5">New parameter</h4>
			<b-form @submit="addParameter">
				<div class="row">
					<div class="col-3">
						Parameter:
						<input v-model="newParameter.name" class="form-control">
					</div>
					<div class="col">
						Values:
						<b-form-tags v-model="newParameter.values" separator=",;"></b-form-tags>
					</div>
					<div class="col-2">
						Is variant?
						<div><input type="checkbox" v-model="newParameter.variant"></div>
					</div>
				</div>
				<b-button type="submit" variant="primary" class="mt-3">Add parameter</b-button>
			</b-form>
			<div class="row">
				<div class="col">
					<h4 class="mt-5">System colors</h4>
				</div>
				<div class="col-sm-4 text-right">
					<b-button variant="primary" size="sm" v-b-modal.modal-add-color>Add color</b-button>
				</div>
			</div>
			<div class="row">
				<div v-for="color in colors" class="col-sm-2">
					<b-card no-body>
						<div :style="'background:' + color.value + ';height:96px'">
							<template v-if="color.imgPath !== null">
								<img :src="basePath + '/' + color.imgPath" :alt="color.color" class="w-100">
							</template>
						</div>
						<div class="container-fluid">
							<div class="row py-2">
								<div class="col">
									{{ color.color }}
								</div>
								<div class="col-2 text-right">
									<b-button variant="danger" @click="removeColor(color.id)" class="px-1 py-0">x</b-button>
								</div>
							</div>
						</div>
					</b-card>
				</div>
			</div>
		</template>
		<b-modal id="modal-add-color" title="Přidat novou systémovou barvu" size="lg" hide-footer>
			<p>
				System colours are used to unify the list of available colours for matching variants.
				Before you can create a new variant with a color, you must add that color
				to the database of available colors under its name. This ensures,
				that, for example, the name <code>red</code> means <code>#F00</code> everywhere in the HEXA system
				and the color will never be rendered incorrectly.
			</p>
			<b-form @submit="addColor">
				<p class="my-0 mt-3">Color name:</p>
				<b-form-input v-model="newColor.color"></b-form-input>
				<p class="my-0 mt-3">HEXA code:</p>
				<b-form-input v-model="newColor.value"></b-form-input>
				<b-button type="submit" variant="primary" class="mt-3">Add color</b-button>
			</b-form>
		</b-modal>
	</b-card>`,
	data() {
		return {
			parameters: null,
			colors: [],
			newParameter: {
				name: '',
				values: [],
				variant: false
			},
			newColor: {
				color: '',
				value: ''
			}
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/parameters?productId=${this.id}`)
				.then(req => {
					this.parameters = req.data.parameters;
					this.colors = req.data.colors;
				});
		},
		addParameter(evt) {
			evt.preventDefault();
			axiosApi.post('cms-product/add-parameter', {
				productId: this.id,
				name: this.newParameter.name,
				values: this.newParameter.values,
				variant: this.newParameter.variant,
			}).then(req => {
				this.newParameter = {
					name: '',
					values: [],
					variant: false
				};
				this.sync();
			});
		},
		deleteParameter(id) {
			if (confirm('Really?')) {
				axiosApi.post('cms-product/delete-parameter', {
					id: id
				}).then(req => {
					this.sync();
				});
			}
		},
		saveChanges(evt) {
			evt.preventDefault();
			axiosApi.post('cms-product/save-parameters', {
				parameters: this.parameters
			}).then(req => {
				this.sync();
			});
		},
		addColor(evt) {
			evt.preventDefault();
			axiosApi.get(`cms-product/add-color?color=${encodeURIComponent(this.newColor.color)}&value=${encodeURIComponent(this.newColor.value)}`)
				.then(req => {
					if (req.data.state === 'ok') {
						this.newColor = {
							color: '',
							value: ''
						};
						this.sync();
					}
				});
		},
		removeColor(id) {
			if (confirm('Do you really want to remove this color?')) {
				axiosApi.get(`cms-product/remove-color?id=${id}`)
					.then(req => {
						this.sync();
					});
			}
		}
	}
});
