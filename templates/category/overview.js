Vue.component('cms-product-category-overview', {
	props: ['id'],
	template: `<cms-card>
	<div v-if="category === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<template v-else>
		<b-alert :show="options.code === false || options.slug === false" variant="warning">
			<b>Important note:</b><br>
			If you change the category code or slug,
			you can break connections to other components of the e-shop or break links (SEO).
			Only make the change if you know what you're doing.
		</b-alert>
		<div class="row">
			<div class="col">
				<b-form @submit="save">
					<div class="row">
						<div class="col">
							Name:
							<b-form-input v-model="category.name"></b-form-input>
						</div>
						<div class="col">
							<div class="container-fluid">
								<div class="row">
									<div class="col px-0">
										Code:
									</div>
									<div class="col px-0 text-right">
										<span v-if="options.code" class="text-secondary" @click="options.code=false">(edit)</span>
									</div>
								</div>
							</div>
							<b-form-input v-model="category.code" :disabled="options.code"></b-form-input>
						</div>
						<div class="col">
							<div class="container-fluid">
								<div class="row">
									<div class="col px-0">
										Slug:
									</div>
									<div class="col px-0 text-right">
										<span v-if="options.slug" class="text-secondary" @click="options.slug=false">(edit)</span>
									</div>
								</div>
							</div>
							<b-form-input v-model="category.slug" :disabled="options.slug"></b-form-input>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col">
							Parent:
							<b-form-select v-model="category.parentId" :options="tree"></b-form-select>
						</div>
						<div class="col">
							<label>
								Active?<br>
								<b-form-checkbox v-model="category.active"></b-form-checkbox>
							</label>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col">
							<cms-editor v-model="category.description" label="Description:" rows="5"></cms-editor>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col">
							<b-button type="submit" variant="primary" @click="save">Save</b-button>
						</div>
					</div>
				</b-form>
			</div>
			<div class="col-3">
				<b-form @submit="processUpload">
					<div class="row">
						<div class="col">
							<b-form-file v-model="upload.file" accept="image/*"></b-form-file>
							<b-form-select v-model="upload.type" :options="upload.types" size="sm" class="mt-2"></b-form-select>
						</div>
						<div class="col-sm-3 pl-0">
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
				<hr>
				<strong>Main photo:</strong><br>
				<div v-if="category.mainPhotoUrl">
					<img :src="category.mainPhotoUrl" class="w-100">
				</div>
				<div v-else class="text-center my-3"><i>No photo.</i></div>
				<hr>
				<strong>Main thumbnail:</strong><br>
				<div v-if="category.mainThumbnailUrl">
					<img :src="category.mainThumbnailUrl" class="w-100">
				</div>
				<div v-else class="text-center my-3"><i>No image.</i></div>
			</div>
		</div>
	</template>
</cms-card>`,
	data() {
		return {
			category: null,
			tree: null,
			options: {
				code: true,
				slug: true,
			},
			upload: {
				file: null,
				type: null,
				types: [
					{text: '--- select type ---', value: null},
					{text: 'Main photo', value: 'main-photo'},
					{text: 'Thumbnail', value: 'thumbnail'},
				],
				loading: false,
			},
		};
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi
				.get(`cms-product-category/overview?id=${this.id}`)
				.then((req) => {
					this.category = req.data.category;
					this.tree = req.data.tree;
				});
		},
		save(evt) {
			evt.preventDefault();
			axiosApi
				.post('cms-product-category/save', {
					id: this.id,
					name: this.category.name,
					code: this.category.code,
					slug: this.category.slug,
					parentId: this.category.parentId,
					description: this.category.description,
					active: this.category.active,
				})
				.then(() => {
					this.sync();
				});
		},
		processUpload(evt) {
			evt.preventDefault();
			if (this.upload.type === null) {
				alert('Please select type first.');
				return;
			}
			this.upload.loading = true;
			let formData = new FormData();
			formData.append('categoryId', this.id);
			formData.append('type', this.upload.type);
			formData.append('mainImage', this.upload.file);
			axiosApi
				.post('cms-product-category/upload-image', formData, {
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				})
				.then(() => {
					this.upload.loading = false;
					this.sync();
				});
		},
	},
});
