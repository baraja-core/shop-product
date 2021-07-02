Vue.component('cms-product-variants', {
	props: ['id'],
	template: `<b-card>
		<div v-if="list === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<div class="row">
				<div class="col">
					<b>Number of variants:</b> {{ variantCount }}
					| <b>Expected number of variants:</b> {{ possibleVariantCount }}
					| <b>Product code:</b> <code>{{ defaultCode }}</code>
					| <b>Product main price:</b> <code>{{ productPrice }}&nbsp;CZK</code>
				</div>
				<div class="col-sm-2 text-right">
					<b-button variant="primary" size="sm" @click="generateVariants">
						<template v-if="loading.generator"><b-spinner small></b-spinner></template>
						<template v-else>Generate variants</template>
					</b-button>
				</div>
			</div>
			<div v-if="variantParameters.length === 0" class="text-center my-3">
				<i>It does not have a table of variants.</i><br>
				On the <b>Parameters</b> tab, create variant parameters and enter values for generating variants.
			</div>
			<div v-else class="mt-3">
				<h4>Variant parameters</h4>
				<table class="table table-sm">
					<tr v-for="(variantParameterValues, variantParameterLabel) in variantParameters">
						<th width="150">{{ variantParameterLabel }}</th>
						<td>
							<span v-for="variantParameterValue in variantParameterValues" class="badge badge-secondary mx-1">
								{{ variantParameterValue }}
							</span>
						</td>
					</tr>
				</table>
			</div>
			<div v-if="list.length === 0" class="text-center my-5">
				<i>There are no variants.</i>
			</div>
			<div v-else class="mt-3">
				<h4>Variants ({{ variantCount }})</h4>
				<table class="table table-sm">
					<tr>
						<th>Variant</th>
						<th width="150"><span v-b-tooltip.hover title="Please indicate if the EAN is different from the base product. If it is the same, it is inherited automatically.">EAN</span></th>
						<th width="150">Code</th>
						<th width="100">Main<br>price</th>
						<th width="100">Additional<br>price</th>
						<th width="100">Selling<br>price</th>
						<th width="150">Availability</th>
						<th width="100"></th>
					</tr>
					<tr v-for="variant in list">
						<td>
							<template v-for="(variantParameterValue, variantParameterLabel) in variant.parameters">
								<b-badge pill variant="light" v-b-tooltip.hover :title="variantParameterLabel">{{ variantParameterValue }}</b-badge>
							</template>
						</td>
						<td>
							<input v-model="variant.ean" class="form-control form-control-sm">
						</td>
						<td>
							<input v-model="variant.code" class="form-control form-control-sm">
						</td>
						<td>
							<input v-model="variant.price" class="form-control form-control-sm">
						</td>
						<td>
							<input v-model="variant.priceAddition" class="form-control form-control-sm">
						</td>
						<td>
							{{ (variant.price * 1) + (variant.priceAddition * 1) }}&nbsp;CZK
						</td>
						<td>
							<b-form-checkbox v-model="variant.soldOut" :value="true" :unchecked-value="false">
								<span v-if="variant.soldOut" class="text-danger">SOLD&nbsp;OUT</span>
								<span v-else class="text-success">In stock</span>
							</b-form-checkbox>
						</td>
						<td class="text-right">
							<b-button variant="danger" @click="remove(variant.id)" size="sm" class="py-0">Remove</b-button>
						</td>
					</tr>
				</table>
				<b-button variant="primary" class="mt-3" @click="saveChanges">Save changes</b-button>
			</div>
			<p class="mt-3">
				<i>
					Variants can only be deleted if they are not included in the order or cart.
					If we allow the deletion of a used variant, it may break the data for users.
					If you want to disable a particular variant from being sold or displayed on the site, mark it as sold out.
				</i>
			</p>
		</template>
	</b-card>`,
	data() {
		return {
			list: null,
			defaultCode: null,
			productPrice: null,
			variantParameters: null,
			variantCount: null,
			possibleVariantCount: null,
			loading: {
				generator: false
			}
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/variants?id=${this.id}`)
				.then(req => {
					this.list = req.data.list;
					this.defaultCode = req.data.defaultCode;
					this.productPrice = req.data.productPrice;
					this.variantParameters = req.data.variantParameters;
					this.variantCount = req.data.variantCount;
					this.possibleVariantCount = req.data.possibleVariantCount;
				});
		},
		generateVariants() {
			this.loading.generator = true;
			axiosApi.get(`cms-product/generate-variants?id=${this.id}`)
				.then(req => {
					this.loading.generator = false;
					this.sync();
				});
		},
		saveChanges(evt) {
			evt.preventDefault();
			axiosApi.post('cms-product/save-variants', {
				id: this.id,
				variants: this.list
			}).then(req => {
				this.sync();
			});
		},
		remove(id) {
			if (confirm('Do you really want to delete this variant?')) {
				axiosApi.post('cms-product/remove-variant', {
					id: id
				}).then(req => {
					this.sync();
				});
			}
		}
	}
});
