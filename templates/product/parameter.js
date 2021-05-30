Vue.component('cms-product-parameter', {
	props: ['id'],
	template: `<b-card>
		<div v-if="parameters === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<h4>Parametry</h4>
			<template v-if="parameters.length > 0">
				<b-form @submit="saveChanges">
					<table class="table table-sm">
						<tr>
							<th>Parametr</th>
							<th>Hodnoty</th>
							<th>Variantní?</th>
							<th></th>
						</tr>
						<tr v-for="parameter in parameters">
							<td><input v-model="parameter.name" class="form-control"></td>
							<td><b-form-tags v-model="parameter.values" separator=",;"></b-form-tags></td>
							<td><input type="checkbox" v-model="parameter.variant"></td>
							<td>
								<b-button variant="danger" size="sm" class="px-1 py-0" @click="deleteParameter(parameter.id)">smazat</b-button>
							</td>
						</tr>
					</table>
					<b-button type="submit" variant="primary" class="mt-3">Uložit změny</b-button>
				</b-form>
			</template>
			<div v-else class="text-center my-5">
				<p>Produkt nemá žádné parametry.</p>
			</div>
			<h4 class="mt-5">Nový parametr</h4>
			<b-form @submit="addParameter">
				<div class="row">
					<div class="col-3">
						Parametr:
						<input v-model="newParameter.name" class="form-control">
					</div>
					<div class="col">
						Hodnoty:
						<b-form-tags v-model="newParameter.values" separator=",;"></b-form-tags>
					</div>
					<div class="col-2">
						Je variantní?
						<div><input type="checkbox" v-model="newParameter.variant"></div>
					</div>
				</div>
				<b-button type="submit" variant="primary" class="mt-3">Přidat parametr</b-button>
			</b-form>
			<div class="row">
				<div class="col">
					<h4 class="mt-5">Systémové barvy</h4>
				</div>
				<div class="col-sm-4 text-right">
					<b-button variant="primary" size="sm" v-b-modal.modal-add-color>Přidat barvu</b-button>
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
				Systémové barvy slouží ke sjednocení výčtu dostupných barev pro párování variant.
				Ještě předtím, než založíte novou variantu s barvou, musíte tuto barvu přidat
				do databáze dostupných barev pod jejím názvem. Tímto je zajištěno,
				že například název <code>červená</code> znamená všude v&nbsp;systému HEXA kód <code>#F00</code>
				a&nbsp;barva nebude nikdy vykreslena chybně.
			</p>
			<b-form @submit="addColor">
				<p class="my-0 mt-3">Název barvy:</p>
				<b-form-input v-model="newColor.color"></b-form-input>
				<p class="my-0 mt-3">Kód barvy:</p>
				<b-form-input v-model="newColor.value"></b-form-input>
				<b-button type="submit" variant="primary" class="mt-3">Přidat barvu</b-button>
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
			if (confirm('Opravdu?')) {
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
			if (confirm('Opravdu chcete odebrat tuto barvu?')) {
				axiosApi.get(`cms-product/remove-color?id=${id}`)
					.then(req => {
						this.sync();
					});
			}
		}
	}
});
