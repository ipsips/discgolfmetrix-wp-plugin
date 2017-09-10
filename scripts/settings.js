const $ = jQuery

class DiscGolfMetrixSettings {
  constructor() {
    this.$filtersSelected = $('.filters-selected')
    this.$filtersSelectedInput = $('input[name="discgolfmetrix_options[results_filter]"]')

    $('.discgolfmetrix-results-filter').sortable({
      connectWith: '.discgolfmetrix-results-filter'
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

new DiscGolfMetrixSettings()
