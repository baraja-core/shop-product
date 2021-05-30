Vue.component('cms-product-images', {
	props: ['id'],
	template: `<b-card>
	<div v-if="images === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div v-else class="container-fluid">
		<div class="row">
			<div class="col">
				<h4>Images</h4>
			</div>
			<div class="col-sm-6">
				<b-form @submit="processUpload">
					<div class="row">
						<div class="col">
							<b-form-file v-model="upload.file" accept="image/*"></b-form-file>
						</div>
						<div class="col-sm-3">
							<b-button variant="primary" type="submit" class="w-100">
								<template v-if="upload.loading">
									<b-spinner small></b-spinner>
								</template>
								<template v-else>
									Upload
								</template>
							</b-button>
						</div>
					</div>
				</b-form>
			</div>
		</div>
		<div v-if="images.length === 0" class="text-center my-5">
			No images.
		</div>
		<template v-else>
			<b-button variant="primary" @click="save">Save changes</b-button>
			<table class="table table-sm mt-2">
				<tr v-for="image in images" :class="{ 'table-secondary': image.id === mainImageId }">
					<td width="200">
						<img :src="basePath + '/product-image/' + image.source" class="w-100">
					</td>
					<td>
						<div>
							<label>
								<input type="radio" v-model="mainImageId" :value="image.id"> Hlavní?
							</label>
						</div>
						<div class="mt-1">
							<input v-model="image.title" class="form-control" placeholder="Zadejte alternativní popisek">
							<div class="row mt-2">
								<div class="col-sm-4">
									Pozice:<br>
									<b-form-spinbutton v-model="image.position" min="0" max="1000"></b-form-spinbutton>
								</div>
								<div class="col">
									Varianta:<br>
									<b-form-select v-model="image.variant" :options="variants"></b-form-select>
								</div>
							</div>
						</div>
					</td>
					<td class="text-right">
						<b-button variant="danger" size="sm" @click="deleteImage(image.id)">x</b-button>
					</td>
				</tr>
			</table>
			<b-button variant="primary" @click="save">Save changes</b-button>
		</template>
	</div>
</b-card>`,
	data() {
		return {
			images: null,
			mainImageId: null,
			variants: [],
			upload: {
				file: null,
				loading: false
			}
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/images?id=${this.id}`)
				.then(req => {
					this.images = req.data.images;
					this.mainImageId = req.data.mainImageId;
					this.variants = req.data.variants;
				});
		},
		save() {
			axiosApi.post('cms-product/save-images', {
				productId: this.id,
				images: this.images,
				mainImageId: this.mainImageId
			}).then(req => {
				this.sync();
			});
		},
		processUpload(evt) {
			evt.preventDefault();
			this.upload.loading = true;
			let formData = new FormData();
			formData.append('productId', this.id);
			formData.append('mainImage', this.upload.file);
			axiosApi.post('cms-product/upload-image', formData, {
				headers: {
					'Content-Type': 'multipart/form-data'
				}
			}).then(req => {
				this.upload.loading = false;
				this.sync();
			});
		},
		deleteImage(id) {
			if (confirm('Opravdu chcete smazat tento obrázek?')) {
				axiosApi.get(`cms-product/delete-image?id=${id}`)
					.then(req => {
						this.sync();
					});
			}
		}
	}
});
