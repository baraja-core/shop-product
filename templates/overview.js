Vue.component('cms-product-overview', {
	props: ['id'],
	template: `<b-card>
	<div v-if="product === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div v-else class="container-fluid">
		<b-form @submit="save">
			<div class="row">
				<div class="col-sm-2">
					<template v-if="product.mainImage === null">
						<i>Nemá hlavní obrázek</i>
					</template>
					<template v-else>
						<img :src="product.mainImage.url" class="w-100" style="max-width:200px;max-height:200px">
					</template>
				</div>
				<div class="col">
					<div class="row">
						<div class="col-4">
							Název:
							<input v-model="product.name" class="form-control">
						</div>
						<div class="col-4">
							Kód produktu:
							<input v-model="product.code" class="form-control">
						</div>
						<div class="col-3">
							EAN produktu:
							<input v-model="product.ean" class="form-control">
						</div>
						<div class="col-1">
							<b-button type="submit" variant="primary" class="mt-3">
								<template v-if="editDynamicDescription.uploading">
									<b-spinner small></b-spinner>
								</template>
								<template v-else>
									Uložit
								</template>
							</b-button>
						</div>
					</div>
					<div class="row my-3">
						<div class="col-2">
							Hlavní cena (v&nbsp;Kč):
							<input v-model="product.price" class="form-control">
						</div>
						<div class="col-1">
							Sleva %:
							<input v-model="product.standardPricePercentage" class="form-control">
						</div>
						<div class="col-1">
							DPH:
							<input v-model="product.vat" class="form-control">
						</div>
						<div class="col-3">
							Hlavní kategorie:
							<b-form-select v-model="product.mainCategoryId" :options="product.categories"></b-form-select>
						</div>
						<div class="col-1">
							ID:
							<input v-model="product.id" class="form-control" disabled>
						</div>
						<div class="col-4">
							URL slug:
							<input v-model="product.slug" class="form-control">
						</div>
					</div>
					<div class="row my-3">
						<div class="col-10">
							URL: <code><a :href="product.url" target="_blank">{{ product.url }}</a></code>
						</div>
						<div class="col-1">
							Aktivní?<br>
							<b-form-checkbox v-model="product.active" switch></b-form-checkbox>
						</div>
						<div class="col-1">
							Vyprod.?<br>
							<b-form-checkbox v-model="product.soldOut" switch></b-form-checkbox>
						</div>
					</div>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col-6">
					Krátký popis:
					<textarea v-model="product.shortDescription" class="form-control" rows="8"></textarea>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col-12">
					<div class="row">
						<div class="col">
							<h4>Dynamické popisky</h4>
						</div>
						<div class="col-3 text-right">
							<b-button variant="secondary" size="sm" v-b-modal.modal-add-description>Přidat popisek</b-button>
						</div>
					</div>
					<div v-if="product.dynamicDescriptions.length === 0" class="text-center my-3">
						<i>Dynamické popisky neexistují.</i>
					</div>
					<table v-else class="table table-sm">
						<tr>
							<th>Popis</th>
							<th width="200">Obrázek</th>
							<th width="80">Pořadí</th>
							<th width="64"></th>
						</tr>
						<tr v-for="dynamicDescription in product.dynamicDescriptions">
							<td>
								<p v-if="dynamicDescription.color === null" class="text-danger">Nastavte barvu!</p>
								<div class="card px-3 py-2"
									:style="'background:' + (dynamicDescription.color ? dynamicDescription.color : '#eee')"
									v-html="dynamicDescription.html"></div>
							</td>
							<td>
								<template v-if="dynamicDescription.image !== null">
									<img :src="basePath + '/' + dynamicDescription.image">
								</template>
							</td>
							<td>
								{{ dynamicDescription.position }}
							</td>
							<td class="text-right">
								<b-button variant="secondary" size="sm" class="px-1 py-0"
									@click="editDescription(dynamicDescription)"
									v-b-modal.modal-edit-description>edit</b-button>
								<b-button variant="danger" size="sm" class="px-1 py-0" @click="deleteDescription(dynamicDescription.id)">smazat</b-button>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col">
					<b-button type="submit" variant="primary">Uložit</b-button>
				</div>
			</div>
		</b-form>
	</div>
	<b-modal id="modal-add-description" title="Nový dynamický popisek" size="lg" hide-footer>
		<b-form @submit="createNewDescription">
			Popis:
			<textarea v-model="newDynamicDescription.description" class="form-control" rows="12"></textarea>
			<div class="row my-3">
				<div class="col">
					Preferované pořadí:
					<input v-model="newDynamicDescription.position" class="form-control">
				</div>
				<div class="col">
					Barva pozadí:
					<b-form-select v-model="newDynamicDescription.color" :options="dynamicColors"></b-form-select>
				</div>
			</div>
			<b-button type="submit" variant="primary" class="mt-3">Přidat nový popisek</b-button>
		</b-form>
	</b-modal>
	<b-modal id="modal-edit-description" title="Editace dynamického popisku" size="lg" hide-footer>
		<b-form @submit="saveEditDescription">
			Popis:
			<textarea v-model="editDynamicDescription.description" class="form-control" rows="12"></textarea>
			<div class="row my-3">
				<div class="col">
					Preferované pořadí:
					<input v-model="editDynamicDescription.position" class="form-control">
				</div>
				<div class="col">
					Barva pozadí:
					<b-form-select v-model="editDynamicDescription.color" :options="dynamicColors"></b-form-select>
				</div>
				<div class="col">
					Obrázek:
					<b-form-file v-model="editDynamicDescription.file" accept="image/*"></b-form-file>
				</div>
			</div>
			<b-button type="submit" variant="primary" class="mt-3">
				<template v-if="editDynamicDescription.uploading">
					<b-spinner small></b-spinner>
				</template>
				<template v-else>
					Uložit změny
				</template>
			</b-button>
		</b-form>
	</b-modal>
	</b-card>`,
	data() {
		return {
			product: null,
			newDynamicDescription: {
				description: '',
				position: 0,
				color: null
			},
			editDynamicDescription: {
				id: null,
				description: '',
				position: 0,
				color: null,
				file: null,
				uploading: false
			},
			dynamicColors: [
				{value: null, text: 'Vyberte'},
				{value: '#F1F4F9', text: '[#F1F4F9] světle šedá'},
				{value: '#B6B7C5', text: '[#B6B7C5] tmavě šedá'},
				{value: '#FFBBB6', text: '[#FFBBB6] červená'},
				{value: '#FECEB3', text: '[#FECEB3] oranžová'},
				{value: '#FEEDB9', text: '[#FEEDB9] žlutá'},
				{value: '#CBF1CF', text: '[#CBF1CF] zelená'},
				{value: '#CAE2F8', text: '[#CAE2F8] modrá'}
			]
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/overview?id=${this.id}`)
				.then(req => {
					this.product = req.data;
				});
		},
		save(evt) {
			evt.preventDefault();
			axiosApi.post('cms-product/save', {
				productId: this.id,
				name: this.product.name,
				code: this.product.code,
				ean: this.product.ean,
				slug: this.product.slug,
				active: this.product.active,
				shortDescription: this.product.shortDescription,
				price: this.product.price,
				standardPricePercentage: this.product.standardPricePercentage,
				vat: this.product.vat,
				soldOut: this.product.soldOut,
				mainCategoryId: this.product.mainCategoryId
			}).then(req => {
				this.sync();
			});
		},
		createNewDescription(evt) {
			evt.preventDefault();
			axiosApi.post('cms-product/add-dynamic-description', {
				productId: this.id,
				description: this.newDynamicDescription.description,
				position: this.newDynamicDescription.position,
			}).then(req => {
				this.newDynamicDescription = {
					description: '',
					position: 0
				};
				this.sync();
			});
		},
		editDescription(description) {
			this.editDynamicDescription.id = description.id;
			this.editDynamicDescription.description = description.description;
			this.editDynamicDescription.position = description.position;
			this.editDynamicDescription.color = description.color;
			this.editDynamicDescription.file = null;
		},
		saveEditDescription(evt) {
			evt.preventDefault();
			this.editDynamicDescription.uploading = true;

			let formData = new FormData();
			formData.append('id', this.editDynamicDescription.id);
			formData.append('description', this.editDynamicDescription.description);
			formData.append('color', this.editDynamicDescription.color);
			formData.append('position', this.editDynamicDescription.position);
			formData.append('image', this.editDynamicDescription.file);

			axiosApi.post('cms-product/save-dynamic-description', formData, {
				headers: {
					'Content-Type': 'multipart/form-data'
				}
			}).then(req => {
				this.editDynamicDescription.uploading = false;
				this.sync();
			});
		},
		deleteDescription(id) {
			if (confirm('Opravdu chcete tento popisek smazat?')) {
				axiosApi.post('cms-product/delete-dynamic-description', {
					productId: this.id,
					descriptionId: id
				}).then(req => {
					this.sync();
				});
			}
		}
	}
});
