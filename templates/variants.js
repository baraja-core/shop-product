Vue.component('cms-product-variants', {
	props: ['id'],
	template: `<b-card>
		<div v-if="list === null" class="text-center my-5">
			<b-spinner></b-spinner>
		</div>
		<template v-else>
			<div class="row">
				<div class="col">
					<b>Počet variant:</b> {{ variantCount }}
					| <b>Očekávaný počet variant:</b> {{ possibleVariantCount }}
					| <b>Kód produktu:</b> <code>{{ defaultCode }}</code>
					| <b>Základní cena produktu:</b> <code>{{ productPrice }}&nbsp;Kč</code>
				</div>
				<div class="col-sm-2 text-right">
					<b-button variant="primary" size="sm" @click="generateVariants">
						<template v-if="loading.generator"><b-spinner small></b-spinner></template>
						<template v-else>Vygenerovat varianty</template>
					</b-button>
				</div>
			</div>
			<div v-if="variantParameters.length === 0" class="text-center my-3">
				<i>Nemá tabulku variant.</i><br>
				V záložce <b>Parametry</b> vytvořte variantní parametry a zadejte hodnoty pro generování variant.
			</div>
			<div v-else class="mt-3">
				<h4>Variantní parametry</h4>
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
				<i>Žádné varianty neexistují.</i>
			</div>
			<div v-else class="mt-3">
				<h4>Varianty ({{ variantCount }})</h4>
				<table class="table table-sm">
					<tr>
						<th>Varianta</th>
						<th width="150"><span v-b-tooltip.hover title="Uveďte, pokud je EAN jiný, než v základním produktu. Pokud je stejný, tak se dědí automaticky.">EAN</span></th>
						<th width="150">Kód</th>
						<th width="100">Základní<br>cena</th>
						<th width="100">Cena<br>přídavek</th>
						<th width="100">Prodejní<br>cena</th>
						<th width="150">Dostupnost</th>
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
							{{ (variant.price * 1) + (variant.priceAddition * 1) }}&nbsp;Kč
						</td>
						<td>
							<b-form-checkbox v-model="variant.soldOut" :value="true" :unchecked-value="false">
								<span v-if="variant.soldOut" class="text-danger">VYPRODÁNO</span>
								<span v-else class="text-success">Skladem</span>
							</b-form-checkbox>
						</td>
						<td class="text-right">
							<b-button variant="danger" @click="remove(variant.id)" size="sm" class="py-0">Smazat</b-button>
						</td>
					</tr>
				</table>
				<b-button variant="primary" class="mt-3" @click="saveChanges">Uložit změny</b-button>
			</div>
			<p class="mt-3">
				<i>
					Varianty lze smazat jen v případě, že nejsou obsaženy v objednávce nebo košíku.
					Pokud bychom umožnili smazání použité varianty, může to uživatelům rozbít data.
					Pokud chcete zakázat prodej konkrétní varianty nebo zobrazení na webu, označte ji jako vyprodanou.
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
			if (confirm('Opravdu chcete smazat tuto variantu?')) {
				axiosApi.post('cms-product/remove-variant', {
					id: id
				}).then(req => {
					this.sync();
				});
			}
		}
	}
});
