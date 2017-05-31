const $ = jQuery

class SkoorinSettings {
  constructor() {
    this.$filtersSelected = $('.filters-selected')
    this.$filtersSelectedInput = $('input[name="skoorin_options[results_filter]"]')

    $('.skoorin-results-filter').sortable({
      connectWith: '.skoorin-results-filter'
    })

    this.$filtersSelected.on('sortupdate', this.onFiltersUpdate)
  }
  onFiltersUpdate = (evt, ui) => {
    const filtersSelected = this.$filtersSelected
      .children()
      .map((idx, el) => $(el).data('name'))
      .toArray()

    console.log(filtersSelected)
    this.$filtersSelectedInput.val(JSON.stringify(filtersSelected))
  }
}

new SkoorinSettings()
