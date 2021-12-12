Vue.component('cms-product-overview', {
	props: ['id'],
	template: `<cms-card>
	<div v-if="product === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div v-else class="container-fluid">
		<b-form @submit="save">
			<div class="row">
				<div class="col" style="max-width:200px">
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
							Name:
							<input v-model="product.name" class="form-control">
						</div>
						<div class="col-4">
							Unique code:
							<input v-model="product.code" class="form-control">
						</div>
						<div class="col-3">
							EAN:
							<input v-model="product.ean" class="form-control">
						</div>
						<div class="col-1">
							<b-button type="submit" variant="primary" class="mt-3">
								<template v-if="editSmartDescription.uploading">
									<b-spinner small></b-spinner>
								</template>
								<template v-else>
									Save
								</template>
							</b-button>
						</div>
					</div>
					<div class="row my-3">
						<div class="col-2">
							Primary price (CZK):
							<input v-model="product.price" class="form-control">
						</div>
						<div class="col-1">
							Sale&nbsp;%
							<input v-model="product.standardPricePercentage" class="form-control">
						</div>
						<div class="col-1">
							VAT:
							<input v-model="product.vat" class="form-control">
						</div>
						<div class="col-3">
							Main category:
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
							Active?<br>
							<b-form-checkbox v-model="product.active" switch></b-form-checkbox>
						</div>
						<div class="col-1">
							Sold&nbsp;out?<br>
							<b-form-checkbox v-model="product.soldOut" switch></b-form-checkbox>
						</div>
					</div>
				</div>
			</div>
			<div v-if="product.customFields.length > 0" class="row">
				<div v-for="customField in product.customFields" class="col-3">
					<div class="row">
						<div class="col">
							<span :title="customField.name">{{ customField.label }}</span>:
							<span v-if="customField.required" class="text-danger">*</span>
						</div>
						<div v-if="customField.description" class="col text-secondary text-right">
							<small>{{ customField.description }}</small>
						</div>
					</div>
					<template v-if="customField.type === 'text'">
						<textarea v-model="customField.value" class="form-control" rows="3"></textarea>
					</template>
					<template v-else>
						<input v-model="customField.value" class="form-control">
					</template>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col-6">
					<cms-editor v-model="product.shortDescription" rows="8" label="Short description:"></cms-editor>
				</div>
				<div class="col-6">
					<cms-editor v-model="product.description" rows="8" label="Regular description:"></cms-editor>
				</div>
			</div>
			<div class="row mt-3">
				<div class="col">
					<b-button type="submit" variant="primary">Save</b-button>
				</div>
			</div>
			<hr>
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
					<table v-else class="w-100 cms-table-no-border-top">
						<tr v-for="smartDescription in product.smartDescriptions">
							<td width="30" valign="top">
								{{ smartDescription.position }}
							</td>
							<td valign="top">
								<div class="card px-3 py-2"
									:style="'background:' + (smartDescription.color ? smartDescription.color : '#eee')"
									v-html="smartDescription.html"></div>
								<div v-if="smartDescription.color === null" class="text-right">
									<small class="text-danger">no color</small>
								</div>
							</td>
							<td class="text-center" width="120" valign="top">
								<template v-if="smartDescription.image !== null">
									<img :src="basePath + '/' + smartDescription.image">
								</template>
							</td>
							<td class="text-right" width="64" valign="top">
								<b-button variant="secondary" size="sm" class="px-1 py-0"
									@click="editDescription(smartDescription)"
									v-b-modal.modal-edit-description>edit</b-button>
								<b-button variant="danger" size="sm" class="px-1 py-0" @click="deleteDescription(smartDescription.id)">remove</b-button>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</b-form>
	</div>
	<b-modal id="modal-add-description" title="New smart description" size="lg" hide-footer>
		<b-form @submit="createNewDescription">
			<cms-editor v-model="newSmartDescription.description" rows="12"></cms-editor>
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
			<cms-editor v-model="editSmartDescription.description" rows="12"></cms-editor>
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
	<b-modal id="modal-clone-product" title="Clone this product" @shown="clonePrepare" hide-footer>
		<b-alert variant="warning" :show="true">
			<b>Before you start:</b><br>
			Product code and slug must be unique.
		</b-alert>
		<b-form @submit="saveClone">
			<div>
				Name:
				<b-form-input v-model="formClone.name"></b-form-input>
			</div>
			<div class="mt-3">
				Code:
				<b-form-input v-model="formClone.code"></b-form-input>
			</div>
			<div class="mt-3">
				Slug:
				<b-form-input v-model="formClone.slug"></b-form-input>
			</div>
			<b-button type="submit" variant="primary" class="mt-3">
				<template v-if="formClone.loading">
					<b-spinner small></b-spinner>
				</template>
				<template v-else>
					Clone
				</template>
			</b-button>
		</b-form>
	</b-modal>
	</cms-card>`,
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
			formClone: {
				loading: false,
				name: '',
				code: '',
				slug: ''
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
			axiosApi.post('cms-product/save', this.product)
				.then(() => {
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
		},
		clonePrepare() {
			this.formClone.name = this.product.name;
			this.formClone.code = this.product.code;
			this.formClone.slug = this.product.slug;
		},
		saveClone(evt) {
			evt.preventDefault();
			this.formClone.loading = true;
			axiosApi.post('cms-product/clone', {
				id: this.id,
				name: this.formClone.name,
				code: this.formClone.code,
				slug: this.formClone.slug
			}).then(req => {
				this.formClone.loading = false;
				if (req.data.id) {
					window.location.href = link('Product:detail', {
						id: req.data.id
					});
				}
			});
		}
	}
});
