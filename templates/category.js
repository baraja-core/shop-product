Vue.component('cms-product-category', {
	props: ['id'],
	template: `<b-card>
	<div v-if="categories === null" class="text-center my-5">
		<b-spinner></b-spinner>
	</div>
	<div class="row" v-else>
		<div class="col">
			<h3>Hlavní kategorie</h3>
			<b-card class="mb-3">{{ mainCategory }}</b-card>
			<p><i>Editace je na záložce "Přehled".</i></p>
		</div>
		<div class="col">
			<h3>Další podkategorie</h3>
			<p>Další kategorie se používají k doupřesnění zařazení produktů do dalších kategorií a mají nižší prioritu, než hlavní kategorie. Také se pozitivně projevují na SEO.</p>
			<div class="text-right mb-3">
				<b-button variant="primary" size="sm" v-b-modal.modal-add-category>Přidat podkategorii</b-button>
			</div>
			<b-card v-if="categories.length === 0">
				Produkt nemá žádné podkategorie. Nastavte první.
			</b-card>
			<table v-else class="table table-sm">
				<tr>
					<th width="48">ID</th>
					<th>Kategorie</th>
					<th width="100">Akce</th>
				</tr>
				<tr v-for="subCategory in categories">
					<td>{{ subCategory.id }}</td>
					<td>{{ subCategory.name }}</td>
					<td class="text-center">
						<b-button variant="danger" size="sm" class="px-2 py-0" @click="deleteCategory(subCategory.id)">smazat</b-button>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<b-modal id="modal-add-category" title="Přidat podkategorii" size="lg" @shown="loadCandidates" hide-footer>
		<div v-if="candidates === null" class="my-5 text-center">
			<b-spinner></b-spinner>
		</div>
		<table v-else class="table table-sm">
			<tr>
				<th>Kategorie</th>
				<th class="text-right">Akce</th>
			</tr>
			<tr v-for="candidate in candidates">
				<td>
					{{ candidate.name }}
				</td>
				<td class="text-right">
					<b-button variant="secondary" size="sm" class="px-2 py-0" @click="addSubCategory(candidate.id)">+</b-button>
				</td>
			</tr>
		</table>
	</b-modal>
</b-card>`,
	data() {
		return {
			mainCategory: null,
			categories: null,
			candidates: null
		}
	},
	mounted() {
		this.sync();
	},
	methods: {
		sync() {
			axiosApi.get(`cms-product/categories?id=${this.id}`)
				.then(req => {
					this.mainCategory = req.data.mainCategory;
					this.categories = req.data.categories;
				});
		},
		loadCandidates() {
			axiosApi.get(`cms-product/related-categories?id=${this.id}`)
				.then(req => {
					this.candidates = req.data.items;
				});
		},
		addSubCategory(id) {
			axiosApi.get(`cms-product/add-category?productId=${this.id}&categoryId=${id}`)
				.then(req => {
					this.loadCandidates();
					this.sync();
				});
		},
		deleteCategory(id) {
			axiosApi.get(`cms-product/delete-category?productId=${this.id}&categoryId=${id}`)
				.then(req => {
					this.sync();
				});
		}
	}
});
