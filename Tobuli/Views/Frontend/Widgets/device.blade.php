<div class="widget widget-device">
    <div class="widget-body">
        <div class="panel-section">
            <div class="panel-section__title op-section-head"
                 role="button"
                 data-toggle="collapse"
                 data-target="#op_location_time_body"
                 aria-expanded="true">
                <i class="icon map"></i>
                <span>{{ trans('front.location_and_time') }}</span>

                <button type="button"
                        class="op-eye js-op-eye"
                        aria-label="toggle address visibility"
                        onclick="event.preventDefault(); event.stopPropagation(); var on = this.classList.toggle('is-on'); var host = document.querySelector('.widget-device'); if (host) host.classList.toggle('op-hide-values', on);">
                    <i class="fas fa-eye"></i>
                </button>

                <i class="fas fa-chevron-up op-section-caret"></i>
            </div>

            <div class="collapse in" id="op_location_time_body">
                <table class="table table--panel">
                    <tbody>
                    <tr>
                        <td>{{ trans('front.address') }}:</td>
                        <td class="{{ settings('plugins.device_widget_full_address.status') ? 'full-text' : '' }}">
                            <span data-device="preview"></span>
                            <span data-device="address" data-preview="true"></span>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ trans('front.time') }}:</td>
                        <td><span data-device="time"></span></td>
                    </tr>
                    <tr>
                        <td>{{ trans('front.stop_duration') }}:</td>
                        <td><span data-device="stop_duration"></span></td>
                    </tr>
                    <tr>
                        <td>{{ trans('front.driver') }}:</td>
                        <td><span data-device="driver.name"></span></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-section">
            <div class="panel-section__title">
                <i class="icon map"></i> {{ trans('front.customer_info') }}
            </div>
            <table class="table table--panel">
                <tbody>
                <tr>
                    <td>{{ trans('front.customer_name') }}:</td>
                    <td><span data-device="name"></span></td>
                </tr>
                <tr>
                    <td>{{ trans('front.contact_no') }}:</td>
                    <td><span data-device="driver.phone"></span></td>
                </tr>
                <tr>
                    <td>{{ trans('front.tpin') }}:</td>
                    <td><span data-device="tpin"></span></td>
                </tr>
                <tr>
                    <td>{{ trans('front.instruction') }}:</td>
                    <td><span data-device="instruction"></span></td>
                </tr>
                <tr>
                    <td>{{ trans('front.geofence') }}:</td>
                    <td><span data-device="geofence"></span></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
