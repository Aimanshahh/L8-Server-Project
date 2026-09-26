<div class="widget widget-device">
    <div class="widget-body">
        <div class="panel-section">
            <div class="panel-section__title">
                <i class="icon map"></i> {{ trans('front.location_and_time') }}
            </div>
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