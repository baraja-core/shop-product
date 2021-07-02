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
						<img src="https://cdn.baraja.cz/icon/no-product-image.jpg" class="w-100" alt="No product image" style="max-width:200px;max-height:200px">
					</template>
					<template v-else>
						<img :src="product.mainImage.url" class="w-100" :alt="product.name" style="max-width:200px;max-height:200px">
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
								<template v-if="editSmartDescription.uploading">
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
							<h4>Smart descriptions</h4>
						</div>
						<div class="col-3 text-right">
							<b-button variant="secondary" size="sm" v-b-modal.modal-add-description>Add description</b-button>
						</div>
					</div>
					<div v-if="product.smartDescriptions.length === 0" class="text-center text-secondary my-3">
						<i>Here is not smart description.</i>
					</div>
					<table v-else class="table table-sm">
						<tr>
							<th>Description</th>
							<th width="200">Media</th>
							<th width="80">Position</th>
							<th width="64"></th>
						</tr>
						<tr v-for="smartDescription in product.smartDescriptions">
							<td>
								<p v-if="smartDescription.color === null" class="text-danger">Please choose color!</p>
								<div class="card px-3 py-2"
									:style="'background:' + (smartDescription.color ? smartDescription.color : '#eee')"
									v-html="smartDescription.html"></div>
							</td>
							<td>
								<template v-if="smartDescription.image !== null">
									<img :src="basePath + '/' + smartDescription.image">
								</template>
							</td>
							<td>
								{{ smartDescription.position }}
							</td>
							<td class="text-right">
								<b-button variant="secondary" size="sm" class="px-1 py-0"
									@click="editDescription(smartDescription)"
									v-b-modal.modal-edit-description>edit</b-button>
								<b-button variant="danger" size="sm" class="px-1 py-0" @click="deleteDescription(smartDescription.id)">remove</b-button>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col">
					<b-button type="submit" variant="primary">Save</b-button>
				</div>
			</div>
		</b-form>
	</div>
	<b-modal id="modal-add-description" title="New smart description" size="lg" hide-footer>
		<b-form @submit="createNewDescription">
			Description:
			<textarea v-model="newSmartDescription.description" class="form-control" rows="12"></textarea>
			<div class="row my-3">
				<div class="col">
					Position:
					<input v-model="newSmartDescription.position" class="form-control">
				</div>
				<div class="col">
					Background color:
					<b-form-select v-model="newSmartDescription.color" :options="smartColors"></b-form-select>
				</div>
			</div>
			<b-button type="submit" variant="primary" class="mt-3">Add new description</b-button>
		</b-form>
	</b-modal>
	<b-modal id="modal-edit-description" title="Edit smart description" size="lg" hide-footer>
		<b-form @submit="saveEditDescription">
			Description:
			<textarea v-model="editSmartDescription.description" class="form-control" rows="12"></textarea>
			<div class="row my-3">
				<div class="col">
					Position:
					<input v-model="editSmartDescription.position" class="form-control">
				</div>
				<div class="col">
					Background color:
					<b-form-select v-model="editSmartDescription.color" :options="smartColors"></b-form-select>
				</div>
				<div class="col">
					Image:
					<b-form-file v-model="editSmartDescription.file" accept="image/*"></b-form-file>
				</div>
			</div>
			<b-button type="submit" variant="primary" class="mt-3">
				<template v-if="editSmartDescription.uploading">
					<b-spinner small></b-spinner>
				</template>
				<template v-else>
					Save changes
				</template>
			</b-button>
		</b-form>
	</b-modal>
	</b-card>`,
	data() {
		return {
			product: null,
			newSmartDescription: {
				description: '',
				position: 0,
				color: null
			},
			editSmartDescription: {
				id: null,
				description: '',
				position: 0,
				color: null,
				file: null,
				uploading: false
			},
			smartColors: [
				{value: null, text: '--- select ---'},
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
			axiosApi.post('cms-product/add-smart-description', {
				productId: this.id,
				description: this.newSmartDescription.description,
				position: this.newSmartDescription.position,
			}).then(req => {
				this.newSmartDescription = {
					description: '',
					position: 0
				};
				this.sync();
			});
		},
		editDescription(description) {
			this.editSmartDescription.id = description.id;
			this.editSmartDescription.description = description.description;
			this.editSmartDescription.position = description.position;
			this.editSmartDescription.color = description.color;
			this.editSmartDescription.file = null;
		},
		saveEditDescription(evt) {
			evt.preventDefault();
			this.editSmartDescription.uploading = true;

			let formData = new FormData();
			formData.append('id', this.editSmartDescription.id);
			formData.append('description', this.editSmartDescription.description);
			formData.append('color', this.editSmartDescription.color);
			formData.append('position', this.editSmartDescription.position);
			formData.append('image', this.editSmartDescription.file);

			axiosApi.post('cms-product/save-smart-description', formData, {
				headers: {
					'Content-Type': 'multipart/form-data'
				}
			}).then(req => {
				this.editSmartDescription.uploading = false;
				this.sync();
			});
		},
		deleteDescription(id) {
			if (confirm('Do you really want to delete this smart description?')) {
				axiosApi.post('cms-product/delete-smart-description', {
					productId: this.id,
					descriptionId: id
				}).then(req => {
					this.sync();
				});
			}
		}
	}
});
