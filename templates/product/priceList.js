Vue.component('cms-product-price-list', {
	props: ['id'],
	template: `<cms-card>
	<div v-if="mainCurrency === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<template v-else>
		Main currency: <b>{{ mainCurrency }}</b>
		<table class="table table-sm table-bordered">
			<tr v-for="productPrice in productPriceList">
				<th>{{ productPrice.currency }}</th>
				<td>{{ productPrice.price }}</td>
				<td>
					<span v-if="productPrice.isManual" class="text-success">Manually defined value.</span>
					<span v-else class="text-warning">Automatically computed</span>
				</td>
			</tr>
		</table>
		<hr>
		<div v-if="productPriceListVariant.length === 0" class="text-center my-5 text-secondary">
			Variants does not exist.
		</div>
		<table v-else class="table table-sm cms-table-no-border-top">
			<tr>
				<th>Variant</th>
				<th v-for="currency in currencies" class="text-center" width="100">{{ currency }}</th>
			</tr>
			<tr v-for="variant in productPriceListVariant">
				<td>{{ variant.label }}</td>
				<td v-for="variantPrice in variant.priceList" :class="variantPrice.isManual ? 'alert-success' : 'alert-warning'">
					<b-form-input v-model="variantPrice.price" size="sm"></b-form-input>
				</td>
			</tr>
		</table>
	</template>
</cms-card>`,
	data() {
		return {
			mainCurrency: null,
			productPriceList: [],
			productPriceListVariant: [],
			currencies: []
		}
	},
	created() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/price-list?id=${this.id}`)
				.then(req => {
					this.mainCurrency = req.data.mainCurrency;
					this.productPriceList = req.data.productPriceList;
					this.productPriceListVariant = req.data.productPriceListVariant;
					this.currencies = req.data.currencies;
				});
		}
	}
});
